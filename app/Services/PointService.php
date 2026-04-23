<?php

namespace App\Services;

use App\Models\Point;
use App\Models\Time;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PointService
{
    public function getPointsPaginated(int $perPage = 10, bool $onlyForActiveRoutes = true): LengthAwarePaginator
    {
        return Point::query()
            ->when($onlyForActiveRoutes, fn ($query) => $query->whereHas(
                'route',
                fn ($routeQuery) => $routeQuery->where('is_active', true),
            ))
            ->withCount('times')
            ->with(['route:id,name_en,name_ar'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getPointById(int $id): Point
    {
        return Point::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPoint(array $data): Point
    {
        return Point::query()->create($data);
    }


    // create time for point
    public function createTime(int $pointId, array $data): Time
    {
        return Time::query()->create([
            'pickup_time' => $data['pickup_time'],
            'point_id' => $pointId,
            'is_active' => $data['is_active'],
        ]);
    }
    public function updatePoint(Point $point, array $data): Point
    {
        $point->update($data);

        return $point->fresh();
    }

    public function deletePoint(Point $point): void
    {
        $point->delete();
    }
}
