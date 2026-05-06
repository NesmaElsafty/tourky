<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;

class AdminDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getOverview(?string $chartDate = null): array
    {
        $tz = config('app.timezone');
        $now = Carbon::now($tz);

        $thisWeekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $thisWeekEnd = $now->copy()->endOfWeek(Carbon::SUNDAY);
        $lastWeekStart = $thisWeekStart->copy()->subWeek();
        $lastWeekEnd = $thisWeekStart->copy()->subSecond();

        $day = $chartDate !== null
            ? Carbon::parse($chartDate, $tz)->startOfDay()
            : $now->copy()->startOfDay();

        return [
            'comparison_period' => [
                'type' => 'week',
                'current_week_start' => $thisWeekStart->toIso8601String(),
                'current_week_end' => $thisWeekEnd->toIso8601String(),
                'previous_week_start' => $lastWeekStart->toIso8601String(),
                'previous_week_end' => $lastWeekEnd->toIso8601String(),
            ],
            'kpis' => $this->kpis($thisWeekStart, $thisWeekEnd, $lastWeekStart, $lastWeekEnd),
            'charts' => [
                'chart_date' => $day->toDateString(),
                'clients_by_bucket' => $this->clientsChartByBucket($day),
                'ride_volume_by_bucket' => $this->rideVolumeChartByBucket($day),
            ],
        ];
    }

    /**
     * @return array<string, array<string, float|int|string|null>>
     */
    private function kpis(
        Carbon $thisWeekStart,
        Carbon $thisWeekEnd,
        Carbon $lastWeekStart,
        Carbon $lastWeekEnd,
    ): array {
        $totalClients = User::query()->where('type', 'client')->count();
        $newClientsThisWeek = User::query()
            ->where('type', 'client')
            ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])
            ->count();
        $newClientsLastWeek = User::query()
            ->where('type', 'client')
            ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
            ->count();

        $activeCaptains = User::query()
            ->where('type', 'captain')
            ->where(function ($q): void {
                $q->whereIn('status', ['available', 'on_trip'])
                    ->orWhereNull('status');
            })
            ->count();

        $newCaptainsThisWeek = User::query()
            ->where('type', 'captain')
            ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])
            ->count();
        $newCaptainsLastWeek = User::query()
            ->where('type', 'captain')
            ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
            ->count();

        $completedThisWeek = Trip::query()
            ->where('status', 'completed')
            ->whereBetween('date', [$thisWeekStart->toDateString(), $thisWeekEnd->toDateString()])
            ->count();
        $completedLastWeek = Trip::query()
            ->where('status', 'completed')
            ->whereBetween('date', [$lastWeekStart->toDateString(), $lastWeekEnd->toDateString()])
            ->count();

        $ratingThisWeek = Reservation::query()
            ->whereNotNull('captain_rating')
            ->whereBetween('dropped_off_at', [$thisWeekStart, $thisWeekEnd])
            ->avg('captain_rating');
        $ratingLastWeek = Reservation::query()
            ->whereNotNull('captain_rating')
            ->whereBetween('dropped_off_at', [$lastWeekStart, $lastWeekEnd])
            ->avg('captain_rating');

        $ratingChangePercent = null;
        if ($ratingThisWeek !== null && $ratingLastWeek !== null) {
            $ratingChangePercent = $this->percentChange((float) $ratingThisWeek, (float) $ratingLastWeek);
        }

        return [
            'total_clients' => [
                'label_key' => 'total_clients',
                'value' => $totalClients,
                'change_percent' => $this->percentChange($newClientsThisWeek, $newClientsLastWeek),
                'subtitle' => 'new_clients_week_over_week',
            ],
            'active_captains' => [
                'label_key' => 'active_captains',
                'value' => $activeCaptains,
                'change_percent' => $this->percentChange($newCaptainsThisWeek, $newCaptainsLastWeek),
                'subtitle' => 'new_captains_week_over_week',
            ],
            'completed_rides' => [
                'label_key' => 'completed_rides',
                'value' => $completedThisWeek,
                'change_percent' => $this->percentChange($completedThisWeek, $completedLastWeek),
                'subtitle' => 'trips_completed_this_week',
            ],
            'average_rating' => [
                'label_key' => 'average_rating',
                'value' => $ratingThisWeek !== null ? round((float) $ratingThisWeek, 2) : null,
                'change_percent' => $ratingChangePercent,
                'subtitle' => 'from_rated_dropoffs_this_week',
            ],
        ];
    }

    /**
     * New client registrations per 3-hour bucket on the given calendar day.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    private function clientsChartByBucket(Carbon $day): array
    {
        $tz = config('app.timezone');
        $buckets = $this->threeHourBuckets($day->copy()->timezone($tz)->startOfDay());

        $labels = [];
        $values = [];

        foreach ($buckets as $bucket) {
            $labels[] = $bucket['label'];
            $values[] = (int) User::query()
                ->where('type', 'client')
                ->whereBetween('created_at', [$bucket['start'], $bucket['end']])
                ->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Completed ride legs (drop-offs) per 3-hour bucket on the given calendar day.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    private function rideVolumeChartByBucket(Carbon $day): array
    {
        $tz = config('app.timezone');
        $buckets = $this->threeHourBuckets($day->copy()->timezone($tz)->startOfDay());

        $labels = [];
        $values = [];

        foreach ($buckets as $bucket) {
            $labels[] = $bucket['label'];
            $values[] = (int) Reservation::query()
                ->whereNotNull('dropped_off_at')
                ->whereBetween('dropped_off_at', [$bucket['start'], $bucket['end']])
                ->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Same bucket definitions as the fleet UI: 06→09 … 21→24, then 00→06.
     *
     * @return list<array{label: string, start: Carbon, end: Carbon}>
     */
    private function threeHourBuckets(Carbon $startOfDay): array
    {
        $out = [];
        $ranges = [
            ['label' => '06:00', 'from' => 6, 'to' => 9],
            ['label' => '09:00', 'from' => 9, 'to' => 12],
            ['label' => '12:00', 'from' => 12, 'to' => 15],
            ['label' => '15:00', 'from' => 15, 'to' => 18],
            ['label' => '18:00', 'from' => 18, 'to' => 21],
            ['label' => '21:00', 'from' => 21, 'to' => 24],
            ['label' => '00:00', 'from' => 0, 'to' => 6],
        ];

        foreach ($ranges as $r) {
            $start = $startOfDay->copy()->addHours($r['from']);
            $end = $startOfDay->copy()->addHours($r['to']);
            $out[] = [
                'label' => $r['label'],
                'start' => $start,
                'end' => $end,
            ];
        }

        return $out;
    }

    private function percentChange(float|int $current, float|int $previous): ?float
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : null;
        }

        return round(($current - $previous) / $previous * 100.0, 1);
    }
}
