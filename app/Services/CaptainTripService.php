<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TripCar;
use App\Models\User;
use App\Support\OperationalWeek;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CaptainTripService
{
    /**
     * @param  'today'|'upcoming'|'week'|'history'  $scope
     * @return LengthAwarePaginator<int, Trip>
     */
    public function getTripsForCaptain(
        User $captain,
        int $perPage = 10,
        string $scope = 'history',
        int $weekOffset = 0,
    ): LengthAwarePaginator {
        if (! in_array($scope, ['upcoming', 'week', 'history', 'today'], true)) {
            $scope = 'history';
        }

        $query = $this->captainTripsBaseQuery($captain);

        match ($scope) {
            'upcoming' => $this->applyUpcomingScope($query),
            'week' => $this->applyWeekUpcomingScope($query, max(0, $weekOffset)),
            'today' => $this->applyTodayScope($query),
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
     * Upcoming trips assigned to this captain today (pickup time still in the future).
     *
     * @return Collection<int, Trip>
     */
    public function getTodayTripsForCaptain(User $captain): Collection
    {
        $query = $this->captainTripsBaseQuery($captain);
        $this->applyTodayScope($query);

        return $query
            ->orderBy('times.pickup_time')
            ->get();
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
     * Today's trips with pickup still ahead of now (planned or in progress).
     *
     * @param  Builder<Trip>  $query
     */
    private function applyTodayScope(Builder $query): void
    {
        $today = now()->toDateString();
        $nowTime = now()->format('H:i');

        $query->where('trips.date', $today)
            ->whereIn('trips.status', ['planned', 'in_progress'])
            ->where('times.pickup_time', '>', $nowTime);
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
     * Upcoming trips within an operational week (Sun–Thu).
     *
     * @param  Builder<Trip>  $query
     */
    private function applyWeekUpcomingScope(Builder $query, int $weekOffset = 0): void
    {
        $this->applyNextTripScope($query);

        $bounds = OperationalWeek::bounds(null, $weekOffset);
        $query->whereBetween('trips.date', [
            $bounds['start']->toDateString(),
            $bounds['end']->toDateString(),
        ]);
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

        $routeTimeId = $trip->route_time_id;

        return Trip::query()
            ->whereKey($trip->id)
            ->with([
                'time:id,pickup_time',
                'routeTime:id,route_id,time_ids',
                'routeTime.route:id,name_en,name_ar,start_point_en,start_point_ar,start_lat,start_long',
                'tripCars' => static fn ($q) => $q->where('captain_id', $captain->id),
                'tripCars.captain:id,name,phone,lat,long,status,has_trip,trip_id',
                'tripCars.car:id,name,type,number_of_seats,plate_numbers,plate_letters,color',
                'reservations' => static function ($q) use ($tripCars, $routeTimeId): void {
                    $q->whereIn('trip_car_id', $tripCars->all())
                        ->when(
                            $routeTimeId !== null,
                            static fn ($q2) => $q2->where('route_time_id', $routeTimeId),
                        )
                        ->with(['user:id,name,phone'])
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
