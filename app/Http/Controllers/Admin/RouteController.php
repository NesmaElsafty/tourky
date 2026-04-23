<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\RouteResource;
use App\Models\Route;
use App\Models\User;
use App\Services\RouteService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RouteController extends Controller
{
    public function __construct(private RouteService $routeService) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->validate([
                'per_page' => 'sometimes|integer|min:1|max:100',
                'type' => ['sometimes', 'nullable', Rule::in(['b2b', 'b2c'])],
                'company_id' => 'sometimes|nullable|integer|exists:users,id',
            ]);

            $serviceFilters = $this->routeFiltersForActor($request, array_filter(
                [
                    'type' => $filters['type'] ?? null,
                    'company_id' => $filters['company_id'] ?? null,
                ],
                static fn ($v) => $v !== null && $v !== '',
            ));

            $routes = $this->routeService->getRoutesPaginated(
                (int) ($filters['per_page'] ?? $request->per_page ?? 10),
                onlyActive: true,
                filters: $serviceFilters,
            );
            $pagination = PaginationHelper::paginate($routes);

            return response()->json([
                'status' => 'success',
                'message' => __('api.routes.list_retrieved'),
                'data' => RouteResource::collection($routes),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexAll(Request $request)
    {
        try {
            if ($request->user()?->isCompanyOperator()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.forbidden_permission'),
                ], 403);
            }

            $filters = $request->validate([
                'per_page' => 'sometimes|integer|min:1|max:100',
                'type' => ['sometimes', 'nullable', Rule::in(['b2b', 'b2c'])],
                'company_id' => 'sometimes|nullable|integer|exists:users,id',
            ]);

            $serviceFilters = $this->routeFiltersForActor($request, array_filter(
                [
                    'type' => $filters['type'] ?? null,
                    'company_id' => $filters['company_id'] ?? null,
                ],
                static fn ($v) => $v !== null && $v !== '',
            ));

            $routes = $this->routeService->getRoutesPaginated(
                (int) ($filters['per_page'] ?? $request->per_page ?? 10),
                onlyActive: false,
                filters: $serviceFilters,
            );
            $pagination = PaginationHelper::paginate($routes);

            return response()->json([
                'status' => 'success',
                'message' => __('api.routes.list_retrieved'),
                'data' => RouteResource::collection($routes),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, Route $route)
    {
        try {
            /** @var User|null $actor */
            $actor = $request->user();
            if ($actor instanceof User && $actor->isCompanyOperator()) {
                if ($route->type !== 'b2b' || (int) $route->company_id !== (int) $actor->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => __('api.routes.not_found'),
                    ], 404);
                }
            }

            $maySeeInactive = $route->is_active
                || ($actor instanceof User && $actor->hasPermission('routes.manage'))
                || ($actor instanceof User
                    && $actor->isCompanyOperator()
                    && $route->type === 'b2b'
                    && (int) $route->company_id === (int) $actor->id);

            if (! $maySeeInactive) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.routes.not_found'),
                ], 404);
            }

            $route->loadMissing([
                'company',
                'points' => fn ($query) => $query->orderBy('id'),
                'points.times' => fn ($query) => $query->where('is_active', true)->orderBy('pickup_time'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('api.routes.retrieved'),
                'data' => new RouteResource($route),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            if ($request->user()?->isCompanyOperator()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.forbidden_permission'),
                ], 403);
            }

            $data = $request->validate([
                'name_en' => 'required|string|max:255',
                'name_ar' => 'required|string|max:255',
                'start_point_en' => 'nullable|string|max:255',
                'start_point_ar' => 'nullable|string|max:255',
                'start_lat' => 'nullable|string|max:255',
                'start_long' => 'nullable|string|max:255',
                'end_point_en' => 'nullable|string|max:255',
                'end_point_ar' => 'nullable|string|max:255',
                'end_lat' => 'nullable|string|max:255',
                'end_long' => 'nullable|string|max:255',
                'type' => ['required', Rule::in(['b2b', 'b2c'])],
                'company_id' => ['nullable', 'integer', 'exists:users,id'],
                'is_active' => 'sometimes|boolean',
            ]);
            if ($data['type'] === 'b2c') {
                $data['company_id'] = null;
            }
            if ($data['type'] === 'b2b' && empty($data['company_id'])) {
                throw ValidationException::withMessages([
                    'company_id' => [__('validation.required', ['attribute' => 'company id'])],
                ]);
            }
            $route = $this->routeService->createRoute($data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.routes.created'),
                'data' => new RouteResource($route),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Route $route)
    {
        try {
            if ($request->user()?->isCompanyOperator()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.forbidden_permission'),
                ], 403);
            }

            $data = $request->validate([
                'name_en' => 'sometimes|required|string|max:255',
                'name_ar' => 'sometimes|required|string|max:255',
                'start_point_en' => 'nullable|string|max:255',
                'start_point_ar' => 'nullable|string|max:255',
                'start_lat' => 'nullable|string|max:255',
                'start_long' => 'nullable|string|max:255',
                'end_point_en' => 'nullable|string|max:255',
                'end_point_ar' => 'nullable|string|max:255',
                'end_lat' => 'nullable|string|max:255',
                'end_long' => 'nullable|string|max:255',
                'type' => ['sometimes', Rule::in(['b2b', 'b2c'])],
                'company_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
                'is_active' => 'sometimes|boolean',
            ]);
            $effectiveType = $data['type'] ?? $route->type;
            if ($effectiveType === 'b2c') {
                $data['company_id'] = null;
            }
            $effectiveCompanyId = array_key_exists('company_id', $data)
                ? $data['company_id']
                : $route->company_id;
            if ($effectiveType === 'b2b' && ($effectiveCompanyId === null || $effectiveCompanyId === '')) {
                throw ValidationException::withMessages([
                    'company_id' => [__('validation.required', ['attribute' => 'company id'])],
                ]);
            }
            $route = $this->routeService->updateRoute($route, $data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.routes.updated'),
                'data' => new RouteResource($route),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, Route $route)
    {
        try {
            if ($request->user()?->isCompanyOperator()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.forbidden_permission'),
                ], 403);
            }

            $this->routeService->deleteRoute($route);

            return response()->json([
                'status' => 'success',
                'message' => __('api.routes.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @param  array{type?: string, company_id?: int|string|null}  $filters
     * @return array{type?: string, company_id?: int|string|null}
     */
    private function routeFiltersForActor(Request $request, array $filters): array
    {
        $user = $request->user();
        if ($user instanceof User && $user->isCompanyOperator()) {
            return [
                'type' => 'b2b',
                'company_id' => $user->id,
            ];
        }

        return $filters;
    }
}
