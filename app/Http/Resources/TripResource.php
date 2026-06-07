<?php

namespace App\Http\Resources;

use App\Helpers\DistanceHelper;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Trip
 */
class TripResource extends JsonResource
{
    private const MAX_DISTANCE_KM = 5.0;

    private const PRE_PICKUP_WINDOW_MINUTES = 10;

    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'time.point.route:id,start_lat,start_long',
            'tripCars.captain:id,lat,long',
        ]);

        $is_availble_for_captain = $this->resolveIsAvailbleForCaptain();

        return [
            'id' => $this->id,
            'time_id' => $this->time?->id,
            'route_time_id' => $this->route_time_id,
            'pickup_time' => $this->time?->pickup_time,
            'date' => $this->date,
            'status' => $this->status,
            'is_availble_for_captain' => $is_availble_for_captain,
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
                                    'lat' => $tripCar->captain->lat,
                                    'long' => $tripCar->captain->long,
                                    'status' => $tripCar->captain->status,
                                    'has_trip' => $tripCar->captain->has_trip,
                                    'trip_id' => $tripCar->captain->trip_id,
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
            'reports' => $this->when(
                $this->relationLoaded('reports'),
                fn () => TripReportResource::collection($this->reports),
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * 1–2: مسافة Haversine من (captain lat/long) إلى بداية المسار (routes.start_lat / start_long) < 5 km.
     * 3: تاريخ الرحلة = اليوم.
     * 4: الوقت الحالي داخل [وقت الالتقاط − ١٠ دقائق، وقت الالتقاط] (شامل الحدّين).
     */
    private function resolveIsAvailbleForCaptain(): bool
    {
        $route = $this->time?->point?->route;
        if ($route === null || $this->time?->pickup_time === null || $this->date === null) {
            return false;
        }

        if ($route->start_lat === null || $route->start_long === null) {
            return false;
        }

        if (! $this->relationLoaded('tripCars')) {
            return false;
        }

        $tz = (string) config('app.timezone', 'UTC');

        try {
            $tripDateStr = Carbon::parse($this->date, $tz)->toDateString();
        } catch (\Throwable) {
            return false;
        }

        if ($tripDateStr !== now($tz)->toDateString()) {
            return false;
        }

        try {
            $pickupAt = Carbon::parse($tripDateStr.' '.$this->time->pickup_time, $tz);
        } catch (\Throwable) {
            return false;
        }

        $windowStart = $pickupAt->copy()->subMinutes(self::PRE_PICKUP_WINDOW_MINUTES);
        $now = now($tz);
        if (! $now->between($windowStart, $pickupAt, true)) {
            return false;
        }

        $startLat = (float) $route->start_lat;
        $startLng = (float) $route->start_long;

        foreach ($this->tripCars as $tripCar) {
            if (! $tripCar->relationLoaded('captain') || $tripCar->captain === null) {
                continue;
            }

            $captain = $tripCar->captain;
            if ($captain->lat === null || $captain->long === null) {
                continue;
            }

            $km = DistanceHelper::haversineDistance(
                (float) $captain->lat,
                (float) $captain->long,
                $startLat,
                $startLng,
            );

            if ($km < self::MAX_DISTANCE_KM) {
                return true;
            }
        }

        return false;
    }
}
