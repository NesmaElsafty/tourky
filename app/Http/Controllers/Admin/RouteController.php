<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\RouteResource;
use App\Models\Route;
use App\Services\RouteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RouteController extends Controller
{
    public function __construct(private RouteService $routeService) {}

    public function index(Request $request)
    {
        try {
            $routes = $this->routeService->getRoutesPaginated(
                (int) ($request->per_page ?? 10),
                onlyActive: true,
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
            $routes = $this->routeService->getRoutesPaginated(
                (int) ($request->per_page ?? 10),
                onlyActive: false,
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
            if (! $route->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.routes.not_found'),
                ], 404);
            }

            $route->loadMissing([
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
                'is_active' => 'sometimes|boolean',
            ]);
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
                'is_active' => 'sometimes|boolean',
            ]);
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

    public function destroy(Route $route)
    {
        try {
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
}
