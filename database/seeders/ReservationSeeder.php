<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\Time;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    /**
     * How many distinct future dates to seed (spread across the next months).
     */
    private const FUTURE_DATE_COUNT = 12;

    /**
     * Pending reservations per (date, time_id) — each uses a different client.
     */
    private const PENDING_PER_DATE_AND_TIME = 8;

    public function run(): void
    {
        $times = Time::query()
            ->with('point')
            ->whereHas('point')
            ->orderBy('id')
            ->get();

        if ($times->isEmpty()) {
            return;
        }

        $clients = User::query()->where('type', 'client')->get();

        $minClients = self::PENDING_PER_DATE_AND_TIME + 5;
        if ($clients->count() < $minClients) {
            User::factory()
                ->count($minClients - $clients->count())
                ->create([
                    'type' => 'client',
                    'role_id' => null,
                ]);
            $clients = User::query()->where('type', 'client')->get();
        }

        $dates = $this->spreadFutureDates(self::FUTURE_DATE_COUNT);

        foreach ($times as $time) {
            foreach ($dates as $date) {
                foreach ($clients->shuffle()->take(self::PENDING_PER_DATE_AND_TIME) as $client) {
                    Reservation::factory()
                        ->forTime($time)
                        ->create([
                            'user_id' => $client->id,
                            'date' => $date,
                            'status' => 'pending',
                        ]);
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function spreadFutureDates(int $count): array
    {
        $dates = [];
        for ($i = 0; $i < $count; $i++) {
            $dates[] = now()->addDays(2 + $i * 4)->toDateString();
        }

        return array_values(array_unique($dates));
    }
}
