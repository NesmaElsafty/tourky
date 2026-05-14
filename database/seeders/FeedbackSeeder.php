<?php

namespace Database\Seeders;

use App\Models\Feedback;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    private const ROWS = 18;

    public function run(): void
    {
        $reservations = Reservation::query()
            ->whereNotNull('dropped_off_at')
            ->whereNotNull('trip_car_id')
            ->with('tripCar:id,captain_id')
            ->inRandomOrder()
            ->limit(self::ROWS * 2)
            ->get();

        $created = 0;
        foreach ($reservations as $reservation) {
            if ($created >= self::ROWS) {
                break;
            }

            $captainId = $reservation->tripCar?->captain_id;
            if ($captainId === null) {
                continue;
            }

            Feedback::query()->create([
                'client_id' => $reservation->user_id,
                'captain_id' => $captainId,
                'feedback' => fake()->paragraph(),
                'rating' => fake()->numberBetween(1, 5),
            ]);
            $created++;
        }
    }
}
