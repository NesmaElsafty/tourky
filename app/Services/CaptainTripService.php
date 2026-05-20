<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TripCar;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Validation\ValidationException;

class CaptainTripService
{
    /**
     * @param  'today'|'upcoming'|'history'  $scope
     * @return LengthAwarePaginator<int, Trip>
     */
    public function getTripsForCaptain(User $captain, int $perPage = 10, string $scope = 'history'): LengthAwarePaginator
    {
        if ($scope === 'today') {
            return $this->paginateSingleTrip($this->getNextTripTodayForCaptain($captain), $perPage);
        }

        if (! in_array($scope, ['upcoming', 'history'], true)) {
            $scope = 'history';
        }

        $query = $this->captainTripsBaseQuery($captain);

        match ($scope) {
            'upcoming' => $this->applyUpcomingScope($query),
            default => $this->applyHistoryScope($query),
        };

        if ($scope === 'history') {
            $query->orderByDesc('trips.date')->orderByDesc('times.pickup_time');
        } else {
            $query->orderBy('trips.date')->orderBy('times.pickup_time');
        }

        return $query->paginate($perPage);
    }

    /**
     * Captain's next assigned trip from now onward (today or a future date).
     */
    public function getNextTripTodayForCaptain(User $captain): ?Trip
    {
        $query = $this->captainTripsBaseQuery($captain);
        $this->applyNextTripScope($query);

        return $query
            ->orderBy('trips.date')
            ->orderBy('times.pickup_time')
            ->first();
    }

    /**
     * @return Builder<Trip>
     */
    private function captainTripsBaseQuery(User $captain): Builder
    {
        return Trip::query()
            ->select('trips.*')
            ->join('times', 'times.id', '=', 'trips.time_id')
            ->whereHas('tripCars', static fn ($q) => $q->where('captain_id', $captain->id))
            ->with([
                'time:id,pickup_time,point_id',
                'time.point.route:id,name_en,name_ar',
                'tripCars' => static fn ($q) => $q->where('captain_id', $captain->id),
                'tripCars.captain:id,name,phone,lat,long,status,has_trip,trip_id',
                'tripCars.car:id,name,type,number_of_seats,plate_numbers,plate_letters,color',
            ])
            ->withCount([
                'reservations as my_clients_count' => static function ($q) use ($captain): void {
                    $q->whereHas('tripCar', static fn ($q2) => $q2->where('captain_id', $captain->id));
                },
            ]);
    }

    /**
     * Next trip: planned/in_progress, pickup still in the future (today or later dates).
     *
     * @param  Builder<Trip>  $query
     */
    private function applyNextTripScope(Builder $query): void
    {
        $today = now()->toDateString();
        $nowTime = now()->format('H:i');

        $query->whereIn('trips.status', ['planned', 'in_progress'])
            ->where(function (Builder $q) use ($today, $nowTime): void {
                $q->where('trips.date', '>', $today)
                    ->orWhere(function (Builder $q2) use ($today, $nowTime): void {
                        $q2->where('trips.date', '=', $today)
                            ->where('times.pickup_time', '>', $nowTime);
                    });
            });
    }

    /**
     * @param  Builder<Trip>  $query
     */
    private function applyUpcomingScope(Builder $query): void
    {
        $this->applyNextTripScope($query);
    }

    /**
     * @return LengthAwarePaginator<int, Trip>
     */
    private function paginateSingleTrip(?Trip $trip, int $perPage): LengthAwarePaginator
    {
        $items = $trip !== null ? collect([$trip]) : collect();

        return new Paginator(
            $items,
            $items->count(),
            max(1, $perPage),
            1,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * @param  Builder<Trip>  $query
     */
    private function applyHistoryScope(Builder $query): void
    {
        $today = now()->toDateString();
        $nowTime = now()->format('H:i');

        $query->where(function (Builder $q) use ($today, $nowTime): void {
            $q->where('trips.status', 'cancelled')
                ->orWhere('trips.status', 'completed')
                ->orWhere('trips.date', '<', $today)
                ->orWhere(function (Builder $q2) use ($today, $nowTime): void {
                    $q2->where('trips.date', '=', $today)
                        ->where('times.pickup_time', '<', $nowTime);
                });
        });
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
                'tripCars.captain:id,name,phone,lat,long,status,has_trip,trip_id',
                'tripCars.car:id,name,type,number_of_seats,plate_numbers,plate_letters,color',
            ])
            ->with([
                'reservations' => static function ($q) use ($tripCars): void {
                    $q->whereIn('trip_car_id', $tripCars->all())
                        ->with([
                            'user:id,name,phone',
                            'point:id,name_en,name_ar,lat,long',
                            'dropOffTime:id,point_id',
                            'dropOffTime.point:id,name_en,name_ar,lat,long',
                        ])
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
                'tripCars.captain:id,name,phone,lat,long,status,has_trip,trip_id',
                'tripCars.car',
            ]) ?? $trip;
        }

        $trip->update(['status' => 'in_progress']);

        return $trip->fresh([
            'time.point.route',
            'time.point',
            'tripCars' => static fn ($q) => $q->where('captain_id', $captain->id),
            'tripCars.captain:id,name,phone,lat,long,status,has_trip,trip_id',
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
                'tripCars.captain:id,name,phone,lat,long,status,has_trip,trip_id',
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
            'tripCars.captain:id,name,phone,lat,long,status,has_trip,trip_id',
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
