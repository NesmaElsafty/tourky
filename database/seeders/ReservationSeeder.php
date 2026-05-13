<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\RouteTime;
use App\Models\Time;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    /**
     * Cap how many pickup times participate in the dense grids (avoids huge seed runtime if many B2C times exist).
     */
    private const MAX_TIMES_FOR_GRIDS = 22;

    /**
     * Distinct future dates (spread) for dense pending grids.
     */
    private const FUTURE_DATE_COUNT = 28;

    /**
     * Pending reservations per (date, time) — capped by client count in run().
     */
    private const PENDING_PER_DATE_AND_TIME = 18;

    /**
     * Past dates for history / status-mix listings (cancelled, confirmed, pending).
     */
    private const PAST_DATE_COUNT = 18;

    /**
     * Reservations per (past date, time) sample.
     */
    private const PAST_SAMPLE_PER_CELL = 10;

    /**
     * Extra random pending rows (ungrouped volume for admin lists & filters).
     */
    private const BULK_RANDOM_PENDING = 180;

    public function run(): void
    {
        $times = Time::query()
            ->with('point')
            ->whereHas('point', function ($query): void {
                $query->whereHas('route', fn ($routeQuery) => $routeQuery->where('type', 'b2c'));
            })
            ->orderBy('id')
            ->get();

        if ($times->isEmpty()) {
            return;
        }

        $gridTimes = $times->count() > self::MAX_TIMES_FOR_GRIDS
            ? $times->take(self::MAX_TIMES_FOR_GRIDS)
            : $times;

        $clients = User::query()->where('type', 'client')->get();
        if ($clients->isEmpty()) {
            return;
        }

        $futureDates = $this->spreadFutureDates(self::FUTURE_DATE_COUNT);
        $take = min(self::PENDING_PER_DATE_AND_TIME, $clients->count());

        foreach ($gridTimes as $time) {
            $time->loadMissing('point');
            if ($time->point === null) {
                continue;
            }
            $routeTimeId = $this->resolveOrCreateRouteTimeId((int) $time->point->route_id, (int) $time->id);

            foreach ($futureDates as $date) {
                foreach ($clients->shuffle()->take($take) as $client) {
                    Reservation::factory()
                        ->forTime($time)
                        ->create([
                            'user_id' => $client->id,
                            'date' => $date,
                            'status' => 'pending',
                            'route_time_id' => $routeTimeId,
                        ]);
                }
            }
        }

        $pastDates = $this->spreadPastDates(self::PAST_DATE_COUNT);
        $pastTake = min(self::PAST_SAMPLE_PER_CELL, $clients->count());

        foreach ($gridTimes as $time) {
            $time->loadMissing('point');
            if ($time->point === null) {
                continue;
            }
            $routeTimeId = $this->resolveOrCreateRouteTimeId((int) $time->point->route_id, (int) $time->id);

            foreach ($pastDates as $date) {
                foreach ($clients->shuffle()->take($pastTake) as $client) {
                    Reservation::factory()
                        ->forTime($time)
                        ->create([
                            'user_id' => $client->id,
                            'date' => $date,
                            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled']),
                            'route_time_id' => $routeTimeId,
                        ]);
                }
            }
        }

        for ($n = 0; $n < self::BULK_RANDOM_PENDING; $n++) {
            $time = $times->random();
            $time->loadMissing('point');
            if ($time->point === null) {
                continue;
            }
            $routeTimeId = $this->resolveOrCreateRouteTimeId((int) $time->point->route_id, (int) $time->id);
            $date = now()->addDays(fake()->numberBetween(1, 120))->toDateString();

            Reservation::factory()
                ->forTime($time)
                ->create([
                    'user_id' => $clients->random()->id,
                    'date' => $date,
                    'status' => 'pending',
                    'route_time_id' => $routeTimeId,
                ]);
        }
    }

    /**
     * @return list<string>
     */
    private function spreadFutureDates(int $count): array
    {
        $dates = [];
        for ($i = 0; $i < $count; $i++) {
            $dates[] = now()->addDays(2 + $i * 3)->toDateString();
        }

        return array_values(array_unique($dates));
    }

    /**
     * @return list<string>
     */
    private function spreadPastDates(int $count): array
    {
        $dates = [];
        for ($i = 0; $i < $count; $i++) {
            $dates[] = now()->subDays(2 + $i * 2)->toDateString();
        }

        return array_values(array_unique($dates));
    }

    private function resolveOrCreateRouteTimeId(int $routeId, int $timeId): int
    {
        $routeTime = RouteTime::query()
            ->where('route_id', $routeId)
            ->whereJsonContains('time_ids', $timeId)
            ->first();

        if ($routeTime !== null) {
            return (int) $routeTime->id;
        }

        $created = RouteTime::query()->create([
            'route_id' => $routeId,
            'time_ids' => [$timeId],
        ]);

        return (int) $created->id;
    }
}
