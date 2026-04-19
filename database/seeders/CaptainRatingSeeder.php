<?php

namespace Database\Seeders;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CaptainRatingSeeder extends Seeder
{
    /**
     * How many assigned reservations (on a trip + vehicle) receive demo ratings.
     */
    private const RATED_RESERVATIONS_LIMIT = 80;

    public function run(): void
    {
        $candidates = Reservation::query()
            ->whereNotNull('trip_id')
            ->whereNotNull('trip_car_id')
            ->where('status', 'confirmed')
            ->whereNull('captain_rating')
            ->inRandomOrder()
            ->limit(self::RATED_RESERVATIONS_LIMIT)
            ->get();

        if ($candidates->isEmpty()) {
            return;
        }

        foreach ($candidates as $reservation) {
            $droppedOff = Carbon::now()->subMinutes(fake()->numberBetween(60, 60 * 24 * 60));
            $pickedUp = (clone $droppedOff)->subMinutes(fake()->numberBetween(10, 200));

            $reservation->update([
                'picked_up_at' => $pickedUp,
                'dropped_off_at' => $droppedOff,
                'captain_rating' => fake()->numberBetween(1, 5),
                'captain_feedback' => fake()->optional(0.62)->paragraph(),
            ]);
        }
    }
}
