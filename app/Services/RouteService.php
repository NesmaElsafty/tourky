<?php

namespace App\Services;

use App\Models\Route;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RouteService
{
    public function getRoutesPaginated(int $perPage = 10, bool $onlyActive = true): LengthAwarePaginator
    {
        return Route::query()
            ->when($onlyActive, fn ($query) => $query->where('is_active', true))
            ->withCount('points')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getRouteById(int $id): Route
    {
        return Route::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRoute(array $data): Route
    {
        return Route::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRoute(Route $route, array $data): Route
    {
        $route->update($data);

        return $route->fresh();
    }

    public function deleteRoute(Route $route): void
    {
        $route->delete();
    }
}
