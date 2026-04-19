<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Time;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function getAdminReservationsPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Reservation::query()
            ->with(['user:id,name,phone,email,type', 'route', 'point', 'time'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Pending reservations grouped by `date`, then by `time_id`.
     *
     * @return Collection<string, Collection<string, Collection<int, Reservation>>>
     */
    public function getPendingReservationsGroupedByDateAndTime(): Collection
    {
        $reservations = Reservation::query()
            ->where('status', 'pending')
            ->with(['user:id,name,phone,email,type', 'route', 'point', 'time'])
            ->orderBy('date')
            ->orderBy('time_id')
            ->orderBy('id')
            ->get();

        return $reservations
            ->groupBy(static fn (Reservation $reservation): string => (string) $reservation->date)
            ->map(
                static fn (Collection $forDate): Collection => $forDate
                    ->groupBy(static fn (Reservation $reservation): string => (string) $reservation->time_id)
                    ->map(static fn (Collection $group): Collection => $group->values())
            );
    }

    public function updateReservationStatus(Reservation $reservation, string $status): Reservation
    {
        if (! in_array($status, ['confirmed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => [__('api.reservations.status_invalid')],
            ]);
        }

        $reservation->update(['status' => $status]);

        return $reservation->fresh(['user:id,name,phone,email,type', 'route', 'point', 'time'])
            ?? $reservation;
    }

    /**
     * @param  array{time_id: int, date: string}  $data
     */
    public function createReservationForClient(User $client, array $data): Reservation
    {
        if ($client->type !== 'client') {
            throw ValidationException::withMessages([
                'user' => [__('api.reservations.client_only')],
            ]);
        }

        $timeId = (int) $data['time_id'];
        $date = $data['date'];

        $time = Time::query()->with(['point.route'])->find($timeId);

        if ($time === null) {
            throw ValidationException::withMessages([
                'time_id' => [__('api.reservations.invalid_time')],
            ]);
        }

        if (! $time->is_active) {
            throw ValidationException::withMessages([
                'time_id' => [__('api.reservations.inactive_time')],
            ]);
        }

        if ($time->point === null || $time->point->route === null) {
            throw ValidationException::withMessages([
                'time_id' => [__('api.reservations.invalid_time')],
            ]);
        }

        if (! $time->point->route->is_active) {
            throw ValidationException::withMessages([
                'time_id' => [__('api.reservations.inactive_route')],
            ]);
        }

        if (! $this->isScheduledAtOrAfterNow($date, (string) $time->pickup_time)) {
            throw ValidationException::withMessages([
                'date' => [__('api.reservations.invalid_date_past')],
            ]);
        }

        if (Reservation::query()
            ->where('user_id', $client->id)
            ->where('time_id', $time->id)
            ->where('date', $date)
            ->exists()) {
            throw ValidationException::withMessages([
                'time_id' => [__('api.reservations.duplicate_reservation')],
            ]);
        }

        return Reservation::query()->create([
            'user_id' => $client->id,
            'route_id' => $time->point->route_id,
            'point_id' => $time->point_id,
            'time_id' => $time->id,
            'date' => $date,
            'status' => 'pending',
        ]);
    }

    /**
     * @param  'upcoming'|'history'  $scope
     * @return LengthAwarePaginator<int, Reservation>
     */
    public function getClientReservationsPaginated(User $client, string $scope, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->clientReservationBaseQuery($client)
            ->with(['route', 'point', 'time']);

        if ($scope === 'upcoming') {
            $this->applyUpcomingScope($query);
            $query->orderBy('reservations.date')->orderBy('times.pickup_time');
        } elseif ($scope === 'history') {
            $this->applyHistoryScope($query);
            $query->orderByDesc('reservations.date')->orderByDesc('times.pickup_time');
        }

        return $query->paginate($perPage);
    }

    public function cancelReservationForClient(Reservation $reservation): Reservation
    {
        if ($reservation->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => [__('api.reservations.already_cancelled')],
            ]);
        }

        if (! in_array($reservation->status, ['pending', 'confirmed'], true)) {
            throw ValidationException::withMessages([
                'status' => [__('api.reservations.cannot_cancel')],
            ]);
        }

        $reservation->update(['status' => 'cancelled']);

        return $reservation->fresh(['route', 'point', 'time']) ?? $reservation;
    }

    public function deleteReservationForClient(Reservation $reservation): void
    {
        $reservation->delete();
    }

    /**
     * @return Builder<Reservation>
     */
    private function clientReservationBaseQuery(User $client): Builder
    {
        return Reservation::query()
            ->where('reservations.user_id', $client->id)
            ->join('times', 'times.id', '=', 'reservations.time_id')
            ->select('reservations.*');
    }

    /**
     * @param  Builder<Reservation>  $query
     */
    private function applyUpcomingScope(Builder $query): void
    {
        $today = now()->toDateString();
        $nowTime = now()->format('H:i');

        $query->whereIn('reservations.status', ['pending', 'confirmed'])
            ->where(function (Builder $q) use ($today, $nowTime): void {
                $q->where('reservations.date', '>', $today)
                    ->orWhere(function (Builder $q2) use ($today, $nowTime): void {
                        $q2->where('reservations.date', '=', $today)
                            ->where('times.pickup_time', '>=', $nowTime);
                    });
            });
    }

    /**
     * @param  Builder<Reservation>  $query
     */
    private function applyHistoryScope(Builder $query): void
    {
        $today = now()->toDateString();
        $nowTime = now()->format('H:i');

        $query->where(function (Builder $q) use ($today, $nowTime): void {
            $q->where('reservations.status', 'cancelled')
                ->orWhere('reservations.date', '<', $today)
                ->orWhere(function (Builder $q2) use ($today, $nowTime): void {
                    $q2->where('reservations.date', '=', $today)
                        ->where('times.pickup_time', '<', $nowTime);
                });
        });
    }

    private function isScheduledAtOrAfterNow(string $date, string $pickupTime): bool
    {
        $pickupTime = trim($pickupTime);
        if ($pickupTime === '') {
            $pickupTime = '00:00';
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $pickupTime, $m)) {
            $pickupTime = sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        $scheduled = Carbon::parse($date.' '.$pickupTime, config('app.timezone'));

        return $scheduled->greaterThanOrEqualTo(now());
    }
}
