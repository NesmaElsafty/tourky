<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Collection;

/**
 * Aggregates client ratings (per-reservation captain_rating) for captains via trip_cars.
 */
class CaptainRatingService
{
    /** @var array<int, array{average: ?float, count: int}> */
    private array $cache = [];

    /**
     * @return array{average: ?float, count: int}
     */
    public function aggregateForCaptainId(int $captainId): array
    {
        if (! isset($this->cache[$captainId])) {
            $this->loadForCaptainIds([$captainId]);
        }

        return $this->cache[$captainId];
    }

    /**
     * @param  array<int>  $captainIds
     * @return array<int, array{average: ?float, count: int}>
     */
    public function aggregateForCaptainIds(array $captainIds): array
    {
        $this->loadForCaptainIds($captainIds);

        $out = [];
        foreach ($captainIds as $id) {
            $out[$id] = $this->cache[$id] ?? ['average' => null, 'count' => 0];
        }

        return $out;
    }

    /**
     * Preload aggregates for all captains on these reservations (avoids N+1 in ClientTripResource).
     *
     * @param  Collection<int, Reservation>  $reservations
     */
    public function primeForReservations(Collection $reservations): void
    {
        $ids = $reservations
            ->map(fn ($r) => $r->tripCar?->captain_id)
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($ids !== []) {
            $this->loadForCaptainIds($ids);
        }
    }

    public function invalidateCaptain(int $captainId): void
    {
        unset($this->cache[$captainId]);
    }

    /**
     * @param  array<int>  $captainIds
     */
    private function loadForCaptainIds(array $captainIds): void
    {
        $captainIds = array_values(array_unique(array_filter($captainIds)));
        $missing = array_values(array_diff($captainIds, array_keys($this->cache)));
        if ($missing === []) {
            return;
        }

        foreach ($missing as $id) {
            $this->cache[$id] = ['average' => null, 'count' => 0];
        }

        $rows = Reservation::query()
            ->join('trip_cars', 'reservations.trip_car_id', '=', 'trip_cars.id')
            ->whereIn('trip_cars.captain_id', $missing)
            ->whereNotNull('reservations.captain_rating')
            ->groupBy('trip_cars.captain_id')
            ->selectRaw('trip_cars.captain_id as captain_id, avg(reservations.captain_rating) as avg_rating, count(*) as cnt')
            ->get();

        foreach ($rows as $row) {
            $cid = (int) $row->captain_id;
            $this->cache[$cid] = [
                'average' => round((float) $row->avg_rating, 2),
                'count' => (int) $row->cnt,
            ];
        }
    }
}
