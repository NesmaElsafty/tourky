<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\RouteTimeResource;
use App\Models\RouteTime;
use App\Services\RouteTimeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RouteTimeController extends Controller
{
    public function __construct(private RouteTimeService $routeTimeService) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            $routeTimes = $this->routeTimeService->getRouteTimesPaginated((int) ($request->per_page ?? 10));
            $pagination = PaginationHelper::paginate($routeTimes);

            return response()->json([
                'status' => 'success',
                'message' => __('api.route_times.list_retrieved'),
                'data' => RouteTimeResource::collection($routeTimes),
                'pagination' => $pagination,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.route_times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(RouteTime $routeTime)
    {
        try {
            $routeTime = $this->routeTimeService->getRouteTimeById((int) $routeTime->id);

            return response()->json([
                'status' => 'success',
                'message' => __('api.route_times.retrieved'),
                'data' => new RouteTimeResource($routeTime),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.route_times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'route_id' => 'required|integer|exists:routes,id',
                'time_ids' => 'required|array|min:1',
                'time_ids.*' => 'required|integer|exists:times,id',
            ]);

            $routeTime = $this->routeTimeService->createRouteTime($data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.route_times.created'),
                'data' => new RouteTimeResource($routeTime),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.route_times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, RouteTime $routeTime)
    {
        try {
            $data = $request->validate([
                'route_id' => 'sometimes|required|integer|exists:routes,id',
                'time_ids' => 'sometimes|required|array|min:1',
                'time_ids.*' => 'required_with:time_ids|integer|exists:times,id',
            ]);

            $routeTime = $this->routeTimeService->updateRouteTime($routeTime, $data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.route_times.updated'),
                'data' => new RouteTimeResource($routeTime),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.route_times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(RouteTime $routeTime)
    {
        try {
            $this->routeTimeService->deleteRouteTime($routeTime);

            return response()->json([
                'status' => 'success',
                'message' => __('api.route_times.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.route_times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
