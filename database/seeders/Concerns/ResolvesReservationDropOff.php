<?php

namespace Database\Seeders\Concerns;

use App\Models\Reservation;
use App\Models\RouteTime;
use App\Models\Time;
use Illuminate\Support\Collection;

trait ResolvesReservationDropOff
{
    /**
     * Prefer a schedule row that includes the pickup and has at least one later stop.
     */
    protected function resolveRouteTimeIdForPickup(int $routeId, int $pickupTimeId): int
    {
        $candidates = RouteTime::query()
            ->where('route_id', $routeId)
            ->whereJsonContains('time_ids', $pickupTimeId)
            ->get()
            ->sortByDesc(fn (RouteTime $routeTime): int => count($routeTime->time_ids ?? []))
            ->values();

        foreach ($candidates as $routeTime) {
            if ($this->resolveDropOffTimeId($routeTime, $pickupTimeId) !== null) {
                return (int) $routeTime->id;
            }
        }

        $existing = $candidates->first();
        if ($existing !== null) {
            return (int) $existing->id;
        }

        $timeIds = $this->buildTimeIdsForNewRouteTime($routeId, $pickupTimeId);

        $created = RouteTime::query()->create([
            'route_id' => $routeId,
            'time_ids' => $timeIds,
        ]);

        return (int) $created->id;
    }

    /**
     * @return list<int>
     */
    protected function buildTimeIdsForNewRouteTime(int $routeId, int $pickupTimeId): array
    {
        $laterTimeId = Time::query()
            ->where('id', '!=', $pickupTimeId)
            ->whereHas('point', static fn ($q) => $q->where('route_id', $routeId))
            ->orderBy('id')
            ->value('id');

        if ($laterTimeId !== null) {
            return [$pickupTimeId, (int) $laterTimeId];
        }

        return [$pickupTimeId];
    }

    /**
     * Pick a drop-off time from the same route schedule row, after the pickup in time_ids order.
     */
    protected function resolveDropOffTimeId(RouteTime $routeTime, int $pickupTimeId): ?int
    {
        $timeIds = collect($routeTime->time_ids ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        $pickupIndex = $timeIds->search($pickupTimeId, strict: true);
        if ($pickupIndex === false) {
            return null;
        }

        $laterIds = $timeIds->slice($pickupIndex + 1);
        if ($laterIds->isEmpty()) {
            return null;
        }

        return (int) $laterIds->last();
    }

    protected function backfillReservationsMissingDropOffTimeId(): void
    {
        Reservation::query()
            ->whereNull('drop_off_time_id')
            ->whereNotNull('time_id')
            ->orderBy('id')
            ->chunkById(200, function (Collection $reservations): void {
                foreach ($reservations as $reservation) {
                    $pickupTimeId = (int) $reservation->time_id;
                    $routeTime = null;

                    if ($reservation->route_time_id !== null) {
                        $routeTime = RouteTime::query()->find($reservation->route_time_id);
                    }

                    if ($routeTime === null && $reservation->route_id !== null) {
                        $routeTimeId = $this->resolveRouteTimeIdForPickup((int) $reservation->route_id, $pickupTimeId);
                        $reservation->update(['route_time_id' => $routeTimeId]);
                        $routeTime = RouteTime::query()->find($routeTimeId);
                    }

                    if ($routeTime === null) {
                        continue;
                    }

                    $dropOffTimeId = $this->resolveDropOffTimeId($routeTime, $pickupTimeId);

                    if ($dropOffTimeId === null && $reservation->route_id !== null) {
                        $routeTimeId = $this->resolveRouteTimeIdForPickup((int) $reservation->route_id, $pickupTimeId);
                        if ($routeTimeId !== (int) $reservation->route_time_id) {
                            $reservation->update(['route_time_id' => $routeTimeId]);
                            $routeTime = RouteTime::query()->findOrFail($routeTimeId);
                            $dropOffTimeId = $this->resolveDropOffTimeId($routeTime, $pickupTimeId);
                        }
                    }

                    if ($dropOffTimeId !== null) {
                        $reservation->update(['drop_off_time_id' => $dropOffTimeId]);
                    }
                }
            });
    }

    /**
     * @return array<string, mixed>
     */
    protected function reservationSeedAttributes(
        int $userId,
        int $routeId,
        int $pointId,
        int $timeId,
        int $routeTimeId,
        string $date,
        string $status = 'pending',
        ?int $tripId = null,
    ): array {
        $routeTime = RouteTime::query()->findOrFail($routeTimeId);
        $dropOffTimeId = $this->resolveDropOffTimeId($routeTime, $timeId);

        return [
            'user_id' => $userId,
            'route_id' => $routeId,
            'point_id' => $pointId,
            'time_id' => $timeId,
            'route_time_id' => $routeTimeId,
            'drop_off_time_id' => $dropOffTimeId,
            'date' => $date,
            'status' => $status,
            'trip_id' => $tripId,
        ];
    }
}
