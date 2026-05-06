<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Builds per-stop pickup clock times for shuttle-style routes: each run starts at the first stop,
 * then later stops pick up minutes derived from haversine distance and an average road speed.
 *
 * Used when seeding or generating RouteTime groups where each {@see Time} belongs to a
 * {@see Point} on the same route.
 */
final class ShuttleRouteScheduleService
{
    private const EARTH_RADIUS_KM = 6371.0;

    /** Typical urban shuttle / bus average speed (km/h). */
    private const DEFAULT_SPEED_KMH = 22.0;

    private const MIN_SEGMENT_MINUTES = 2;

    private const MIN_DUPLICATE_MINUTES = 1;

    /** Avoid unrealistic dwell when GPS points are widely spaced on highways. */
    private const MAX_SEGMENT_MINUTES = 90;

    /**
     * For each daily run, returns clock times at every stop in order (first stop uses that run's start).
     *
     * @param  list<array{lat: float|int|string, lon: float|int|string}>  $orderedStops  First → last along the line of travel
     * @param  list<string>  $firstStopPickups  'HH:MM' at the first stop for each run (e.g. morning / afternoon)
     * @return list<list<string>> One row per run; each row has one 'HH:MM' per stop
     */
    public function buildPickupSchedules(array $orderedStops, array $firstStopPickups): array
    {
        $schedules = [];
        foreach ($firstStopPickups as $start) {
            $schedules[] = $this->oneRunSchedule($orderedStops, $start);
        }

        return $schedules;
    }

    /**
     * @param  list<array{lat: float|int|string, lon: float|int|string}>  $orderedStops
     * @return list<string>
     */
    private function oneRunSchedule(array $orderedStops, string $firstStopPickup): array
    {
        $n = count($orderedStops);
        if ($n === 0) {
            return [];
        }

        $cursor = Carbon::createFromFormat('H:i', $firstStopPickup);
        $out = [$cursor->copy()->format('H:i')];

        for ($i = 0; $i < $n - 1; $i++) {
            $segmentMinutes = $this->segmentMinutes(
                $this->coord($orderedStops[$i]),
                $this->coord($orderedStops[$i + 1])
            );
            $cursor->addMinutes($segmentMinutes);
            $out[] = $cursor->format('H:i');
        }

        return $out;
    }

    /**
     * @param  array{lat: mixed, lon: mixed}  $row
     * @return array{lat: float, lon: float}
     */
    private function coord(array $row): array
    {
        return [
            'lat' => (float) $row['lat'],
            'lon' => (float) $row['lon'],
        ];
    }

    /**
     * @param  array{lat: float, lon: float}  $a
     * @param  array{lat: float, lon: float}  $b
     */
    private function segmentMinutes(array $a, array $b): int
    {
        $km = $this->haversineKm($a['lat'], $a['lon'], $b['lat'], $b['lon']);
        if ($km < 0.05) {
            return self::MIN_DUPLICATE_MINUTES;
        }

        $minutes = (int) round($km / self::DEFAULT_SPEED_KMH * 60.0);

        return max(self::MIN_SEGMENT_MINUTES, min($minutes, self::MAX_SEGMENT_MINUTES));
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $lat1r = deg2rad($lat1);
        $lat2r = deg2rad($lat2);
        $h = sin($dLat / 2) ** 2 + cos($lat1r) * cos($lat2r) * sin($dLon / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($h)));
    }
}
