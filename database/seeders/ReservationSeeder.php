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
     * Route-time rows used for dense pending grids (not per pickup stop).
     */
    private const MAX_ROUTE_TIMES_FOR_GRIDS = 12;

    private const FUTURE_DATE_COUNT = 10;

    /** Pending reservations per (date, route_time) — admin groups by route_time_id. */
    private const PENDING_PER_DATE_AND_ROUTE_TIME = 6;

    private const PAST_DATE_COUNT = 8;

    private const PAST_SAMPLE_PER_ROUTE_TIME = 4;

    private const BULK_RANDOM_PENDING = 30;

    public function run(): void
    {
        $routeTimes = RouteTime::query()
            ->whereHas('route', fn ($query) => $query->whereIn('type', ['b2c', 'b2b']))
            ->orderBy('id')
            ->take(self::MAX_ROUTE_TIMES_FOR_GRIDS)
            ->get();

        if ($routeTimes->isEmpty()) {
            return;
        }

        $clients = User::query()->where('type', 'client')->get();
        if ($clients->isEmpty()) {
            return;
        }

        $futureDates = $this->spreadFutureDates(self::FUTURE_DATE_COUNT);
        $futureTake = min(self::PENDING_PER_DATE_AND_ROUTE_TIME, $clients->count());

        foreach ($routeTimes as $routeTime) {
            $pickupTime = $this->resolvePickupTimeForRouteTime($routeTime);
            if ($pickupTime === null) {
                continue;
            }

            $dropOffTimeId = $this->resolveDropOffTimeId($routeTime, (int) $pickupTime->id);

            foreach ($futureDates as $date) {
                foreach ($clients->shuffle()->take($futureTake) as $client) {
                    Reservation::factory()
                        ->forTime($pickupTime)
                        ->create([
                            'user_id' => $client->id,
                            'date' => $date,
                            'status' => 'pending',
                            'route_time_id' => $routeTime->id,
                            'drop_off_time_id' => $dropOffTimeId,
                        ]);
                }
            }
        }

        $pastDates = $this->spreadPastDates(self::PAST_DATE_COUNT);
        $pastTake = min(self::PAST_SAMPLE_PER_ROUTE_TIME, $clients->count());

        foreach ($routeTimes as $routeTime) {
            $pickupTime = $this->resolvePickupTimeForRouteTime($routeTime);
            if ($pickupTime === null) {
                continue;
            }

            $dropOffTimeId = $this->resolveDropOffTimeId($routeTime, (int) $pickupTime->id);

            foreach ($pastDates as $date) {
                foreach ($clients->shuffle()->take($pastTake) as $client) {
                    Reservation::factory()
                        ->forTime($pickupTime)
                        ->create([
                            'user_id' => $client->id,
                            'date' => $date,
                            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled']),
                            'route_time_id' => $routeTime->id,
                            'drop_off_time_id' => $dropOffTimeId,
                        ]);
                }
            }
        }

        $allTimes = Time::query()
            ->with('point')
            ->whereHas('point.route', fn ($query) => $query->whereIn('type', ['b2c', 'b2b']))
            ->get();

        if ($allTimes->isEmpty()) {
            return;
        }

        for ($n = 0; $n < self::BULK_RANDOM_PENDING; $n++) {
            $time = $allTimes->random();
            $time->loadMissing('point');
            if ($time->point === null) {
                continue;
            }

            $routeTime = $this->resolveOrCreateRouteTime((int) $time->point->route_id, (int) $time->id);
            $dropOffTimeId = $this->resolveDropOffTimeId($routeTime, (int) $time->id);
            $date = now()->addDays(fake()->numberBetween(1, 120))->toDateString();

            Reservation::factory()
                ->forTime($time)
                ->create([
                    'user_id' => $clients->random()->id,
                    'date' => $date,
                    'status' => 'pending',
                    'route_time_id' => $routeTime->id,
                    'drop_off_time_id' => $dropOffTimeId,
                ]);
        }
    }

    private function resolvePickupTimeForRouteTime(RouteTime $routeTime): ?Time
    {
        $pickupTimeId = collect($routeTime->time_ids ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->first(static fn (int $id): bool => $id > 0);

        if ($pickupTimeId === null) {
            return null;
        }

        return Time::query()
            ->with('point')
            ->whereKey($pickupTimeId)
            ->first();
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

    private function resolveOrCreateRouteTime(int $routeId, int $timeId): RouteTime
    {
        $routeTime = RouteTime::query()
            ->where('route_id', $routeId)
            ->whereJsonContains('time_ids', $timeId)
            ->first();

        if ($routeTime !== null) {
            return $routeTime;
        }

        return RouteTime::query()->create([
            'route_id' => $routeId,
            'time_ids' => [$timeId],
        ]);
    }

    private function resolveDropOffTimeId(RouteTime $routeTime, int $pickupTimeId): ?int
    {
        $timeIds = collect($routeTime->time_ids ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        $pickupIndex = $timeIds->search($pickupTimeId, strict: true);
        if ($pickupIndex === false) {
            return null;
        }

        $laterIds = $timeIds->slice($pickupIndex + 1);
        if ($laterIds->isEmpty()) {
            return null;
        }

        return (int) $laterIds->random();
    }
}
