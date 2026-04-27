<?php

namespace App\Services;

use App\Models\Time;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TimeService
{
    public function getTimesPaginated(int $perPage = 10, bool $onlyActive = true): LengthAwarePaginator
    {
        return Time::query()
            ->when($onlyActive, function ($query): void {
                $query->where('is_active', true)
                    ->whereHas('point.route', fn ($routeQuery) => $routeQuery->where('is_active', true));
            })
            ->with(['point:id,name_en,name_ar,route_id'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getTimeById(int $id): Time
    {
        return Time::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTime(array $data): Time
    {
        return Time::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTime($id, array $data): Time
    {
        $time = Time::findOrFail($id); 
        $time->pickup_time = $data['pickup_time'] ?? $time->pickup_time;
        $time->is_active = $data['is_active'] ?? $time->is_active;
        $time->save();

        return $time;
    }

    public function deleteTime($id): void
    {
        $time = Time::findOrFail($id);
        $time->delete();
    }
}
