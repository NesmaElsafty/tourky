<?php

namespace Database\Seeders;

use App\Models\CaptainReport;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class CaptainReportSeeder extends Seeder
{
    /**
     * How many assigned reservations receive a demo report (trip and/or captain).
     */
    private const REPORT_RESERVATIONS_LIMIT = 35;

    public function run(): void
    {
        $candidates = Reservation::query()
            ->whereNotNull('trip_id')
            ->whereNotNull('trip_car_id')
            ->with('tripCar')
            ->inRandomOrder()
            ->limit(120)
            ->get();

        if ($candidates->isEmpty()) {
            return;
        }

        $created = 0;

        foreach ($candidates->shuffle() as $reservation) {
            if ($created >= self::REPORT_RESERVATIONS_LIMIT) {
                break;
            }

            $type = fake()->randomElement([CaptainReport::TYPE_TRIP, CaptainReport::TYPE_CAPTAIN]);

            if ($type === CaptainReport::TYPE_CAPTAIN && $reservation->tripCar?->captain_id === null) {
                $type = CaptainReport::TYPE_TRIP;
            }

            if (CaptainReport::query()
                ->where('reservation_id', $reservation->id)
                ->where('type', $type)
                ->exists()) {
                continue;
            }

            CaptainReport::factory()
                ->forReservation($reservation, $type)
                ->create();

            $created++;
        }
    }
}
