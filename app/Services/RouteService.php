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
        $route->update($data);

        return $route->fresh(['company']) ?? $route;
    }

    public function deleteRoute(Route $route): void
    {
        $route->delete();
    }
}
