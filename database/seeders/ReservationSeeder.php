<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\Time;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $times = Time::query()
            ->with('point')
            ->whereHas('point')
            ->get();

        if ($times->isEmpty()) {
            return;
        }

        $clients = User::query()->where('type', 'client')->get();

        if ($clients->isEmpty()) {
            $clients = User::factory()
                ->count(10)
                ->create([
                    'type' => 'client',
                    'role_id' => null,
                ]);
        }

        $reservationCount = min(40, $times->count() * 3);

        for ($i = 0; $i < $reservationCount; $i++) {
            $time = $times->random();
            Reservation::factory()
                ->forTime($time)
                ->create([
                    'user_id' => $clients->random()->id,
                ]);
        }
    }
}
