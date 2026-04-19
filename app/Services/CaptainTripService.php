<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TripCar;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CaptainTripService
{
    /**
     * @return LengthAwarePaginator<int, Trip>
     */
    public function getTripsForCaptain(User $captain, int $perPage = 10): LengthAwarePaginator
    {
        return Trip::query()
            ->whereHas('tripCars', static fn ($q) => $q->where('captain_id', $captain->id))
            ->with([
                'time:id,pickup_time,point_id',
                'time.point.route:id,name_en,name_ar',
                'tripCars' => static fn ($q) => $q->where('captain_id', $captain->id),
                'tripCars.car:id,name,type,number_of_seats,plate_numbers,plate_letters,color',
            ])
            ->withCount([
                'reservations as my_clients_count' => static function ($q) use ($captain): void {
                    $q->whereHas('tripCar', static fn ($q2) => $q2->where('captain_id', $captain->id));
                },
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getTripForCaptain(User $captain, Trip $trip): Trip
    {
        $tripCars = TripCar::query()
            ->where('trip_id', $trip->id)
            ->where('captain_id', $captain->id)
            ->pluck('id');

        if ($tripCars->isEmpty()) {
            throw ValidationException::withMessages([
                'trip' => [__('api.captain_trips.not_assigned')],
            ]);
        }

        return Trip::query()
            ->whereKey($trip->id)
            ->with([
                'time:id,pickup_time,point_id',
                'time.point.route:id,name_en,name_ar',
                'time.point:id,name_en,name_ar,lat,long,route_id',
                'tripCars' => static fn ($q) => $q->where('captain_id', $captain->id),
                'tripCars.car:id,name,type,number_of_seats,plate_numbers,plate_letters,color',
            ])
            ->with([
                'reservations' => static function ($q) use ($tripCars): void {
                    $q->whereIn('trip_car_id', $tripCars->all())
                        ->with('user:id,name,phone')
                        ->orderBy('id');
                },
            ])
            ->firstOrFail();
    }

    public function startTripForCaptain(User $captain, Trip $trip): Trip
    {
        $this->assertCaptainOnTrip($captain, $trip);

        if ($trip->status === 'completed' || $trip->status === 'cancelled') {
            throw ValidationException::withMessages([
                'trip' => [__('api.captain_trips.cannot_start_status')],
            ]);
        }

        if ($trip->status !== 'planned') {
            return $trip->fresh([
                'time.point.route',
                'tripCars' => static fn ($q) => $q->where('captain_id', $captain->id),
                'tripCars.car',
            ]) ?? $trip;
        }

        $trip->update(['status' => 'in_progress']);

        return $trip->fresh([
            'time.point.route',
            'time.point',
            'tripCars' => static fn ($q) => $q->where('captain_id', $captain->id),
            'tripCars.car',
        ]) ?? $trip;
    }

    public function confirmClientPickup(User $captain, Trip $trip, Reservation $reservation): Reservation
    {
        $this->assertCaptainOnTrip($captain, $trip);

        if ($reservation->trip_id !== $trip->id) {
            throw ValidationException::withMessages([
                'reservation' => [__('api.captain_trips.reservation_not_on_trip')],
            ]);
        }

        $tripCar = TripCar::query()
            ->where('id', (int) $reservation->trip_car_id)
            ->where('trip_id', $trip->id)
            ->where('captain_id', $captain->id)
            ->first();

        if ($tripCar === null) {
            throw ValidationException::withMessages([
                'reservation' => [__('api.captain_trips.reservation_not_your_vehicle')],
            ]);
        }

        if (in_array($trip->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'trip' => [__('api.captain_trips.cannot_pickup_status')],
            ]);
        }

        if ($reservation->picked_up_at !== null) {
            return $reservation->fresh(['user:id,name,phone']) ?? $reservation;
        }

        $reservation->update(['picked_up_at' => now()]);

        return $reservation->fresh(['user:id,name,phone']) ?? $reservation;
    }

    /**
     * Passenger left the vehicle (end of their ride on this car).
     */
    public function confirmClientDropoff(User $captain, Trip $trip, Reservation $reservation): Reservation
    {
        $this->assertCaptainOnTrip($captain, $trip);

        if ($reservation->trip_id !== $trip->id) {
            throw ValidationException::withMessages([
                'reservation' => [__('api.captain_trips.reservation_not_on_trip')],
            ]);
        }

        $tripCar = TripCar::query()
            ->where('id', (int) $reservation->trip_car_id)
            ->where('trip_id', $trip->id)
            ->where('captain_id', $captain->id)
            ->first();

        if ($tripCar === null) {
            throw ValidationException::withMessages([
                'reservation' => [__('api.captain_trips.reservation_not_your_vehicle')],
            ]);
        }

        if (in_array($trip->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'trip' => [__('api.captain_trips.cannot_dropoff_status')],
            ]);
        }

        if ($reservation->picked_up_at === null) {
            throw ValidationException::withMessages([
                'reservation' => [__('api.captain_trips.must_arrive_before_dropoff')],
            ]);
        }

        if ($reservation->dropped_off_at !== null) {
            return $reservation->fresh(['user:id,name,phone']) ?? $reservation;
        }

        $reservation->update(['dropped_off_at' => now()]);

        return $reservation->fresh(['user:id,name,phone']) ?? $reservation;
    }

    /**
     * Mark the whole trip as finished (captain ends the run).
     */
    public function closeTripForCaptain(User $captain, Trip $trip): Trip
    {
        $this->assertCaptainOnTrip($captain, $trip);

        if ($trip->status === 'completed') {
            return $trip->fresh([
                'time.point.route',
                'time.point',
                'tripCars' => static fn ($q) => $q->where('captain_id', $captain->id),
                'tripCars.car',
            ]) ?? $trip;
        }

        if ($trip->status === 'cancelled') {
            throw ValidationException::withMessages([
                'trip' => [__('api.captain_trips.cannot_close_cancelled')],
            ]);
        }

        if ($trip->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'trip' => [__('api.captain_trips.must_start_before_close')],
            ]);
        }

        $trip->update(['status' => 'completed']);

        return $trip->fresh([
            'time.point.route',
            'time.point',
            'tripCars' => static fn ($q) => $q->where('captain_id', $captain->id),
            'tripCars.car',
        ]) ?? $trip;
    }

    private function assertCaptainOnTrip(User $captain, Trip $trip): void
    {
        $exists = TripCar::query()
            ->where('trip_id', $trip->id)
            ->where('captain_id', $captain->id)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'trip' => [__('api.captain_trips.not_assigned')],
            ]);
        }
    }
}
