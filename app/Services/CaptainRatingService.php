<?php

namespace App\Services;

use App\Models\CaptainReport;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
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
     * Client-written feedback for admin captain profile (non-empty text only), newest first.
     * Each item is anchored to the trip the captain ran (not only the booking row).
     *
     * @return list<array{trip: array{id: int, date: string|null, status: string|null}, rating: int, feedback: string, passenger: array{id: int, name: string}|null}>
     */
    public function feedbackEntriesForCaptain(int $captainId, int $limit = 100): array
    {
        $reservations = Reservation::query()
            ->select('reservations.*')
            ->join('trip_cars', 'reservations.trip_car_id', '=', 'trip_cars.id')
            ->where('trip_cars.captain_id', $captainId)
            ->whereNotNull('reservations.trip_id')
            ->whereNotNull('reservations.captain_rating')
            ->whereNotNull('reservations.captain_feedback')
            ->whereRaw("trim(reservations.captain_feedback) <> ''")
            ->with([
                'user:id,name',
                'trip:id,date,status',
            ])
            ->orderByDesc('reservations.updated_at')
            ->orderByDesc('reservations.id')
            ->limit($limit)
            ->get();

        return $reservations->map(static function (Reservation $r): array {
            $trip = $r->trip;
            $tripDate = $trip !== null && $trip->date !== null
                ? Carbon::parse($trip->date)->toDateString()
                : $r->date;

            return [
                'trip' => [
                    'id' => (int) $r->trip_id,
                    'date' => $tripDate,
                    'status' => $trip !== null ? (string) $trip->status : null,
                ],
                'rating' => (int) $r->captain_rating,
                'feedback' => (string) $r->captain_feedback,
                'passenger' => $r->relationLoaded('user') && $r->user !== null
                    ? ['id' => (int) $r->user->id, 'name' => (string) $r->user->name]
                    : null,
            ];
        })->values()->all();
    }

    /**
     * Client incident reports for admin captain profile, newest first.
     *
     * @return list<array{report_id: int, type: string, reservation_id: int, trip_id: int, date: string|null, message: string, client: array{id: int, name: string}|null}>
     */
    public function reportEntriesForCaptain(int $captainId, int $limit = 100): array
    {
        $reports = CaptainReport::query()
            ->where('captain_id', $captainId)
            ->where('type', CaptainReport::TYPE_CAPTAIN)
            ->with(['reservation.user:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $reports->map(static function (CaptainReport $r): array {
            $res = $r->reservation;

            return [
                'report_id' => (int) $r->id,
                'type' => (string) $r->type,
                'reservation_id' => (int) $r->reservation_id,
                'trip_id' => (int) $r->trip_id,
                'date' => $res?->date,
                'message' => (string) $r->message,
                'client' => $res !== null && $res->relationLoaded('user') && $res->user !== null
                    ? ['id' => (int) $res->user->id, 'name' => (string) $res->user->name]
                    : null,
            ];
        })->values()->all();
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
