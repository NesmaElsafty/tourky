<?php

namespace Database\Factories;

use App\Models\CaptainReport;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;
use RuntimeException;

/**
 * @extends Factory<CaptainReport>
 */
class CaptainReportFactory extends Factory
{
    protected $model = CaptainReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message' => fake()->realTextBetween(40, 600),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (CaptainReport $report): void {
            if ($report->reservation_id !== null) {
                return;
            }

            $type = fake()->randomElement([CaptainReport::TYPE_TRIP, CaptainReport::TYPE_CAPTAIN]);

            $reservation = Reservation::query()
                ->whereNotNull('trip_id')
                ->whereNotNull('trip_car_id')
                ->whereDoesntHave('reports', static fn ($q) => $q->where('type', $type))
                ->when(
                    $type === CaptainReport::TYPE_CAPTAIN,
                    static fn ($q) => $q->whereHas('tripCar', static fn ($q2) => $q2->whereNotNull('captain_id'))
                )
                ->inRandomOrder()
                ->first();

            if ($reservation === null && $type === CaptainReport::TYPE_CAPTAIN) {
                $type = CaptainReport::TYPE_TRIP;
                $reservation = Reservation::query()
                    ->whereNotNull('trip_id')
                    ->whereNotNull('trip_car_id')
                    ->whereDoesntHave('reports', static fn ($q) => $q->where('type', $type))
                    ->inRandomOrder()
                    ->first();
            }

            if ($reservation === null) {
                $reservation = Reservation::query()
                    ->whereNotNull('trip_id')
                    ->whereNotNull('trip_car_id')
                    ->whereDoesntHave('reports', static fn ($q) => $q->where('type', CaptainReport::TYPE_TRIP))
                    ->inRandomOrder()
                    ->first();
            }

            if ($reservation === null) {
                throw new RuntimeException(
                    'No eligible reservation for CaptainReport. Seed trips first or use forReservation().'
                );
            }

            $reservation->loadMissing('tripCar');

            $captainId = null;
            if ($type === CaptainReport::TYPE_CAPTAIN) {
                if ($reservation->tripCar?->captain_id === null) {
                    $type = CaptainReport::TYPE_TRIP;
                } else {
                    $captainId = (int) $reservation->tripCar->captain_id;
                }
            }

            if (CaptainReport::query()->where('reservation_id', $reservation->id)->where('type', $type)->exists()) {
                throw new RuntimeException('Reservation already has a report of this type. Use forReservation().');
            }

            $report->type = $type;
            $report->reservation_id = $reservation->id;
            $report->trip_id = $reservation->trip_id;
            $report->captain_id = $captainId;
        });
    }

    /**
     * Use an existing assigned reservation. For captain reports the vehicle must have a captain.
     */
    public function forReservation(Reservation $reservation, string $type = CaptainReport::TYPE_CAPTAIN): static
    {
        $reservation->loadMissing('tripCar');

        return $this->state(function () use ($reservation, $type): array {
            if ($reservation->trip_id === null || $reservation->trip_car_id === null) {
                throw new InvalidArgumentException('Reservation must be assigned to a trip and vehicle.');
            }

            if (! in_array($type, [CaptainReport::TYPE_TRIP, CaptainReport::TYPE_CAPTAIN], true)) {
                throw new InvalidArgumentException('Invalid report type.');
            }

            $captainId = null;
            if ($type === CaptainReport::TYPE_CAPTAIN) {
                if ($reservation->tripCar?->captain_id === null) {
                    throw new InvalidArgumentException('Reservation vehicle must have a captain for a captain report.');
                }
                $captainId = (int) $reservation->tripCar->captain_id;
            }

            return [
                'type' => $type,
                'reservation_id' => $reservation->id,
                'trip_id' => $reservation->trip_id,
                'captain_id' => $captainId,
            ];
        });
    }
}
