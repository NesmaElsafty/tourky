<?php

namespace App\Http\Resources;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Trip
 */
class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'time_id' => $this->time_id,
            'date' => $this->date,
            'status' => $this->status,
            'cars' => $this->when(
                $this->relationLoaded('tripCars'),
                function () {
                    $reservations = $this->relationLoaded('reservations')
                        ? $this->reservations
                        : collect();

                    return $this->tripCars->map(function ($tripCar) use ($reservations) {
                        $forCar = $reservations->where('trip_car_id', $tripCar->id);

                        return [
                            'id' => $tripCar->id,
                            'captain' => $tripCar->relationLoaded('captain') && $tripCar->captain !== null
                                ? [
                                    'id' => $tripCar->captain->id,
                                    'name' => $tripCar->captain->name,
                                    'phone' => $tripCar->captain->phone,
                                ]
                                : null,
                            'car' => $tripCar->relationLoaded('car') && $tripCar->car !== null
                                ? [
                                    'id' => $tripCar->car->id,
                                    'name' => $tripCar->car->name,
                                    'number_of_seats' => $tripCar->car->number_of_seats,
                                    'type' => $tripCar->car->type,
                                ]
                                : null,
                            'clients' => $forCar
                                ->filter(fn ($reservation) => $reservation->relationLoaded('user') && $reservation->user !== null)
                                ->map(fn ($reservation) => [
                                    'reservation_id' => $reservation->id,
                                    'client_id' => $reservation->user->id,
                                    'name' => $reservation->user->name,
                                    'phone' => $reservation->user->phone,
                                ])
                                ->values(),
                            'reservations_count' => $forCar->count(),
                        ];
                    });
                }
            ),
            'reservations_count' => $this->when(
                $this->relationLoaded('reservations'),
                fn () => $this->reservations->count()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
