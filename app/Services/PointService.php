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
    
    public function updatePoint($id, array $data): Point
    {
        $point = Point::findOrFail($id);
        $point->name_en = $data['name_en'] ?? $point->name_en;
        $point->name_ar = $data['name_ar'] ?? $point->name_ar;
        $point->lat = $data['lat'] ?? $point->lat;
        $point->long = $data['long'] ?? $point->long;
        $point->route_id = $data['route_id'] ?? $point->route_id;
        $point->save();

        return $point;
    }

    public function deletePoint(Point $point): void
    {
        $point->delete();
    }
}
