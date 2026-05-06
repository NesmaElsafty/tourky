<?php

namespace App\Services;

use App\Models\RouteTime;
use App\Models\Time;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class RouteTimeService
{
    public function getRouteTimesPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return RouteTime::query()
            ->with('route:id,name_en,name_ar,is_active')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getRouteTimeById(int $id): RouteTime
    {
        return RouteTime::query()
            ->with('route:id,name_en,name_ar,is_active')
            ->findOrFail($id);
    }

    /**
     * @param  array{route_id:int,time_ids:array<int,int|string>}  $data
     */
    public function createRouteTime(array $data): RouteTime
    {
        $normalizedTimeIds = $this->normalizeAndValidateTimeIds((int) $data['route_id'], $data['time_ids']);

        return RouteTime::query()->create([
            'route_id' => (int) $data['route_id'],
            'time_ids' => $normalizedTimeIds,
        ])->load('route:id,name_en,name_ar,is_active');
    }

    /**
     * @param  array{route_id?:int,time_ids?:array<int,int|string>}  $data
     */
    public function updateRouteTime(RouteTime $routeTime, array $data): RouteTime
    {
        $routeId = (int) ($data['route_id'] ?? $routeTime->route_id);
        $timeIds = $data['time_ids'] ?? ($routeTime->time_ids ?? []);
        $normalizedTimeIds = $this->normalizeAndValidateTimeIds($routeId, $timeIds);

        $routeTime->update([
            'route_id' => $routeId,
            'time_ids' => $normalizedTimeIds,
        ]);

        return $routeTime->fresh('route:id,name_en,name_ar,is_active') ?? $routeTime;
    }

    public function deleteRouteTime(RouteTime $routeTime): void
    {
        $routeTime->delete();
    }

    /**
     * @param  array<int,int|string>  $timeIds
     * @return list<int>
     */
    private function normalizeAndValidateTimeIds(int $routeId, array $timeIds): array
    {
        $normalized = collect($timeIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($normalized->isEmpty()) {
            throw ValidationException::withMessages([
                'time_ids' => [__('api.route_times.time_ids_required')],
            ]);
        }

        $validCount = Time::query()
            ->whereIn('id', $normalized->all())
            ->whereHas('point', fn ($query) => $query->where('route_id', $routeId))
            ->count();

        if ($validCount !== $normalized->count()) {
            throw ValidationException::withMessages([
                'time_ids' => [__('api.route_times.time_ids_must_belong_to_route')],
            ]);
        }

        return $normalized->all();
    }
}
