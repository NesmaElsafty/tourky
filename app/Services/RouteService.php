<?php

namespace App\Services;

use App\Models\Route;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RouteService
{
    /**
     * @param  array{type?: string, company_id?: int|null}  $filters
     */
    public function getRoutesPaginated(int $perPage = 10, bool $onlyActive = true, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min(100, $perPage));

        return Route::query()
            ->with(['company'])
            ->when($onlyActive, fn ($query) => $query->where('is_active', true))
            ->when(
                ! empty($filters['type']) && in_array($filters['type'], ['b2b', 'b2c'], true),
                fn ($query) => $query->where('type', $filters['type']),
            )
            ->when(
                array_key_exists('company_id', $filters) && $filters['company_id'] !== null && $filters['company_id'] !== '',
                fn ($query) => $query->where('company_id', (int) $filters['company_id']),
            )
            ->withCount('points')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getRouteById(int $id): Route
    {
        return Route::query()->with(['company'])->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRoute(array $data): Route
    {
        $route = Route::query()->create($data);

        return $route->load(['company']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRoute(Route $route, array $data): Route
    {
        $route->name_en = $data['name_en'] ?? $route->name_en;
        $route->name_ar = $data['name_ar'] ?? $route->name_ar;
        $route->start_point_en = $data['start_point_en'] ?? $route->start_point_en;
        $route->start_point_ar = $data['start_point_ar'] ?? $route->start_point_ar;
        $route->start_lat = $data['start_lat'] ?? $route->start_lat;
        $route->start_long = $data['start_long'] ?? $route->start_long;
        $route->end_point_en = $data['end_point_en'] ?? $route->end_point_en;
        $route->end_point_ar = $data['end_point_ar'] ?? $route->end_point_ar;
        $route->end_lat = $data['end_lat'] ?? $route->end_lat;
        $route->end_long = $data['end_long'] ?? $route->end_long;
        $route->type = $data['type'] ?? $route->type;
        $route->company_id = $data['company_id'] ?? $route->company_id;
        $route->is_active = $data['is_active'] ?? $route->is_active;
        $route->point_price = $data['point_price'] ?? $route->point_price;
        $route->save();

        return $route;
    }

    public function deleteRoute(Route $route): void
    {
        $route->delete();
    }
}
