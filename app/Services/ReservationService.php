<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\RouteTime;
use App\Models\Time;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
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
     * Pending reservations grouped by `date`, then by `route_time_id`.
     *
     * @return Collection<string, Collection<string, Collection<int, Reservation>>>
     */
    public function getPendingReservationsGroupedByDateAndRouteTime(): Collection
    {
        $reservations = Reservation::query()
            ->where('status', 'pending')
            ->whereNotNull('route_time_id')
            ->with(['user:id,name,phone,email,type', 'route', 'point', 'time', 'routeTime'])
            ->orderBy('date')
            ->orderBy('route_time_id')
            ->orderBy('time_id')
            ->orderBy('id')
            ->get();

        return $reservations
            ->groupBy(static fn (Reservation $reservation): string => (string) $reservation->date)
            ->map(
                static fn (Collection $forDate): Collection => $forDate
                    ->groupBy(static fn (Reservation $reservation): string => (string) ($reservation->route_time_id ?? 0))
                    ->map(static fn (Collection $group): Collection => $group->values())
            )
            ->sortKeys();
    }

    /**
     * Pending reservation groups summarized by `date + route_time_id`.
     *
     * @return PaginationLengthAwarePaginator<int, array<string, mixed>>
     */
    public function getPendingReservationGroupSummariesPaginated(int $perPage = 10): PaginationLengthAwarePaginator
    {
        $perPage = max(1, min(100, $perPage));

        $groups = Reservation::query()
            ->selectRaw('date, route_time_id, COUNT(*) as reservations_count')
            ->where('status', 'pending')
            ->whereNotNull('route_time_id')
            ->groupBy('date', 'route_time_id')
            ->orderBy('date')
            ->orderBy('route_time_id')
            ->paginate($perPage);

        $routeTimeIds = collect($groups->items())
            ->pluck('route_time_id')
            ->filter()
            ->unique()
            ->values();

        $routeTimesById = RouteTime::query()
            ->with('route:id,name_en,name_ar,is_active')
            ->whereIn('id', $routeTimeIds->all())
            ->get()
            ->keyBy('id');

        $items = collect($groups->items())
            ->map(function ($group) use ($routeTimesById): array {
                $routeTime = $routeTimesById->get((int) $group->route_time_id);
                $timeIds = collect($routeTime?->time_ids ?? [])
                    ->map(static fn ($id): int => (int) $id)
                    ->filter(static fn (int $id): bool => $id > 0)
                    ->values()
                    ->all();

                return [
                    'date' => (string) $group->date,
                    'route_time_id' => (int) $group->route_time_id,
                    'reservations_count' => (int) $group->reservations_count,
                    'route_id' => $routeTime?->route_id,
                    'time_ids' => $timeIds,
                    'route' => $routeTime?->route,
                ];
            })
            ->values()
            ->all();

        return new PaginationLengthAwarePaginator(
            $items,
            $groups->total(),
            $groups->perPage(),
            $groups->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
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
     * @param  array{time_id: int, drop_off_time_id: int, date: string}  $data
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
        $dropOffTimeId = (int) $data['drop_off_time_id'];

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

        $route = $time->point->route;
        if ($route->type === 'b2b') {
            if ($client->company_id === null || (int) $client->company_id !== (int) $route->company_id) {
                throw ValidationException::withMessages([
                    'time_id' => [__('api.reservations.company_route_not_allowed')],
                ]);
            }
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

        $price = $this->calculatePriceForReservation($timeId, $dropOffTimeId);

        $routeTimeId = $this->resolveRouteTimeIdForReservation(
            (int) $time->point->route_id,
            $timeId,
            $dropOffTimeId,
        );

        return Reservation::query()->create([
            'user_id' => $client->id,
            'route_id' => $time->point->route_id,
            'point_id' => $time->point_id,
            'time_id' => $time->id,
            'route_time_id' => $routeTimeId,
            'date' => $date,
            'status' => 'pending',
            'price' => $price,
            'drop_off_time_id' => $dropOffTimeId,
        ]);
    }

    /**
     * @param  'upcoming'|'history'  $scope
     * @return LengthAwarePaginator<int, Reservation>
     */
    public function getClientReservationsPaginated(User $client, string $scope, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->clientReservationBaseQuery($client)
            ->with(['route', 'point', 'time', 'dropOffTime']);

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

    public function calculatePriceForReservation(int $timeId, int $dropOffTimeId): float
    {
        if ($timeId === $dropOffTimeId) {
            throw ValidationException::withMessages([
                'drop_off_time_id' => [__('api.reservations.drop_off_must_differ_from_pickup')],
            ]);
        }

        $time = Time::query()->with('point.route')->find($timeId);
        $dropOffTime = Time::query()->with('point')->find($dropOffTimeId);

        if ($time === null) {
            throw ValidationException::withMessages([
                'time_id' => [__('api.reservations.invalid_time')],
            ]);
        }

        if ($dropOffTime === null) {
            throw ValidationException::withMessages([
                'drop_off_time_id' => [__('api.reservations.invalid_drop_off_time')],
            ]);
        }

        if ($time->point === null || $dropOffTime->point === null) {
            throw ValidationException::withMessages([
                'time_id' => [__('api.reservations.invalid_time')],
            ]);
        }

        $routeId = (int) $time->point->route_id;
        if ((int) $dropOffTime->point->route_id !== $routeId) {
            throw ValidationException::withMessages([
                'drop_off_time_id' => [__('api.reservations.drop_off_different_route')],
            ]);
        }

        $routeTime = RouteTime::query()
            ->where('route_id', $routeId)
            ->containingTimes($timeId, $dropOffTimeId)
            ->first();

        if ($routeTime === null) {
            throw ValidationException::withMessages([
                'drop_off_time_id' => [__('api.reservations.route_time_pair_not_configured')],
            ]);
        }

        $timeIds = collect($routeTime->time_ids ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->values();

        $pickupIndex = $timeIds->search($timeId, strict: true);
        $dropoffIndex = $timeIds->search($dropOffTimeId, strict: true);

        if ($pickupIndex === false || $dropoffIndex === false) {
            throw ValidationException::withMessages([
                'drop_off_time_id' => [__('api.reservations.route_time_pair_not_configured')],
            ]);
        }

        if ($pickupIndex >= $dropoffIndex) {
            throw ValidationException::withMessages([
                'drop_off_time_id' => [__('api.reservations.drop_off_must_be_after_pickup')],
            ]);
        }

        $pointPrice = $time->point->route?->point_price;
        if ($pointPrice === null) {
            return 0.0;
        }

        $pointCount = $dropoffIndex - $pickupIndex + 1;

        return round((float) $pointPrice * $pointCount, 2);
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

    private function resolveRouteTimeIdForReservation(int $routeId, int $pickupTimeId, int $dropOffTimeId): int
    {
        $existing = RouteTime::query()
            ->where('route_id', $routeId)
            ->containingTimes($pickupTimeId, $dropOffTimeId)
            ->first();

        if ($existing !== null) {
            return (int) $existing->id;
        }

        throw ValidationException::withMessages([
            'drop_off_time_id' => [__('api.reservations.route_time_pair_not_configured')],
        ]);
    }
}
