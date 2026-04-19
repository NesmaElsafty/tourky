<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ClientTripService
{
    /**
     * Single assigned trip (by reservation id) for the client, or null if not found / not assigned.
     */
    public function getTripDetailForClient(User $client, Reservation $reservation): ?Reservation
    {
        if ($reservation->user_id !== $client->id) {
            return null;
        }

        if ($reservation->trip_id === null || $reservation->trip_car_id === null) {
            return null;
        }

        return $reservation->loadMissing($this->assignedTripEagerLoads());
    }

    /**
     * Reservations assigned to a vehicle (trip), with route / point / time / captain / car loaded.
     *
     * @return Collection<int, Reservation>
     */
    public function getTodayTripsForClient(User $client): Collection
    {
        $today = now()->toDateString();

        return $this->assignedTripBaseQuery($client)
            ->where('reservations.date', $today)
            ->where('reservations.status', 'confirmed')
            ->orderBy('times.pickup_time')
            ->get();
    }

    /**
     * @return LengthAwarePaginator<int, Reservation>
     */
    public function getUpcomingTripsPaginated(User $client, int $perPage = 10): LengthAwarePaginator
    {
        $today = now()->toDateString();
        $nowTime = now()->format('H:i');

        $query = $this->assignedTripBaseQuery($client)
            ->where('reservations.status', 'confirmed')
            ->where(function (Builder $q) use ($today, $nowTime): void {
                $q->where('reservations.date', '>', $today)
                    ->orWhere(function (Builder $q2) use ($today, $nowTime): void {
                        $q2->where('reservations.date', '=', $today)
                            ->where('times.pickup_time', '>=', $nowTime);
                    });
            })
            ->orderBy('reservations.date')
            ->orderBy('times.pickup_time');

        return $query->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, Reservation>
     */
    public function getHistoryTripsPaginated(User $client, int $perPage = 10): LengthAwarePaginator
    {
        $today = now()->toDateString();
        $nowTime = now()->format('H:i');

        $query = $this->assignedTripBaseQuery($client)
            ->where(function (Builder $q) use ($today, $nowTime): void {
                $q->where('reservations.status', 'cancelled')
                    ->orWhere('reservations.date', '<', $today)
                    ->orWhere(function (Builder $q2) use ($today, $nowTime): void {
                        $q2->where('reservations.date', '=', $today)
                            ->where('times.pickup_time', '<', $nowTime);
                    });
            })
            ->orderByDesc('reservations.date')
            ->orderByDesc('times.pickup_time');

        return $query->paginate($perPage);
    }

    /**
     * Assigned to a trip run with a vehicle (captain + car).
     *
     * @return Builder<Reservation>
     */
    private function assignedTripBaseQuery(User $client): Builder
    {
        return Reservation::query()
            ->where('reservations.user_id', $client->id)
            ->whereNotNull('reservations.trip_id')
            ->whereNotNull('reservations.trip_car_id')
            ->join('times', 'times.id', '=', 'reservations.time_id')
            ->with($this->assignedTripEagerLoads())
            ->select('reservations.*');
    }

    /**
     * @return array<int, string>
     */
    private function assignedTripEagerLoads(): array
    {
        return [
            'route:id,name_en,name_ar,is_active',
            'point:id,name_en,name_ar,lat,long',
            'time:id,pickup_time,point_id',
            'tripCar.captain:id,name,phone',
            'tripCar.car:id,name,number_of_seats,type,plate_numbers,plate_letters,color',
        ];
    }
}
