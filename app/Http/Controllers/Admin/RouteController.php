<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RouteIndexRequest;
use App\Http\Requests\Admin\StoreRouteRequest;
use App\Http\Requests\Admin\UpdateRouteRequest;
use App\Http\Resources\RouteResource;
use App\Models\Route;
use App\Models\User;
use App\Services\RouteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RouteController extends Controller
{
    public function __construct(private RouteService $routeService) {}

    public function index(RouteIndexRequest $request)
    {
        try {
            $filters = $request->validated();

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
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexAll(RouteIndexRequest $request)
    {
        try {
            if ($request->user()?->isCompanyOperator()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.forbidden_permission'),
                ], 403);
            }

            $filters = $request->validated();

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
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $route = Route::query()->findOrFail($id);
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
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreRouteRequest $request)
    {
        try {
            if ($request->user()?->isCompanyOperator()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.forbidden_permission'),
                ], 403);
            }

            $data = $request->validated();
            if ($data['type'] === 'b2c') {
                $data['company_id'] = null;
            }
            if ($data['type'] === 'b2b' && empty($data['company_id'])) {
                throw ValidationException::withMessages([
                    'company_id' => [__('api.routes.validation_company_required')],
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

    public function update(UpdateRouteRequest $request, $id)
    {
        try {
            if ($request->user()?->isCompanyOperator()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.forbidden_permission'),
                ], 403);
            }
            $route = Route::query()->findOrFail($id);
            $data = $request->validated();

            $companyId = $route->company_id;
            if (isset($data['company_id']) && $data['company_id'] !== null) {
                $company = User::query()->findOrFail($data['company_id']);
                if ($company->role->name_en !== 'Company') {
                    return response()->json([
                        'status' => 'error',
                        'message' => __('api.routes.invalid_company'),
                    ], 400);
                }
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

    public function destroy(Request $request, $id)
    {
        try {
            $route = Route::query()->findOrFail($id);
            if ($request->user()?->isCompanyOperator()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.forbidden_permission'),
                ], 403);
            }

            $route->delete();

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
