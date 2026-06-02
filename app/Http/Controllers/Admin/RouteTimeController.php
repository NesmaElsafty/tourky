<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RouteTimeIndexRequest;
use App\Http\Requests\Admin\StoreRouteTimeRequest;
use App\Http\Requests\Admin\UpdateRouteTimeRequest;
use App\Http\Resources\RouteTimeResource;
use App\Models\RouteTime;
use App\Services\RouteTimeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RouteTimeController extends Controller
{
    public function __construct(private RouteTimeService $routeTimeService) {}

    public function index(RouteTimeIndexRequest $request)
    {
        try {
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
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.route_times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $routeTime = RouteTime::query()->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => __('api.route_times.retrieved'),
                'data' => new RouteTimeResource($routeTime),
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.route_times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreRouteTimeRequest $request)
    {
        try {
            $data = $request->validated();

            $routeTime = $this->routeTimeService->createRouteTime($data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.route_times.created'),
                'data' => new RouteTimeResource($routeTime),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.route_times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateRouteTimeRequest $request, $id)
    {
        try {
            $routeTime = RouteTime::query()->findOrFail($id);
            $data = $request->validated();

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

    public function destroy($id)
    {
        try {
            $routeTime = RouteTime::query()->findOrFail($id);
            $routeTime->delete();

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
