<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\PointResource;
use App\Models\Point;
use App\Models\Route;
use App\Models\Time;
use App\Services\PointService;
use App\Services\TimeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PointController extends Controller
{
    public function __construct(private PointService $pointService, private TimeService $timeService) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'route_id' => 'required|exists:routes,id',
            ]);
            $points = $this->pointService->getPointsPaginated($request->route_id);
            $pagination = PaginationHelper::paginate($points);

            return response()->json([
                'status' => 'success',
                'message' => __('api.points.list_retrieved'),
                'data' => PointResource::collection($points),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.points.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request,$id)
    {
        try {
            $point = Point::find($id);
            if($point === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.points.not_found'),
                ], 404);
            }
            $point->loadMissing([
                'route:id,name_en,name_ar,is_active',
                'times' => fn ($query) => $query->where('is_active', true)->orderBy('pickup_time'),
            ]);

            if (! $point->route || ! $point->route->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.points.not_found'),
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('api.points.retrieved'),
                'data' => new PointResource($point),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.points.server_error'),
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
                'lat' => 'nullable|string|max:255',
                'long' => 'nullable|string|max:255',
                'route_id' => 'required|exists:routes,id',
                'times' => 'required|array',
                'times.*.pickup_time' => 'required|string|max:255',
                'times.*.is_active' => 'required|boolean',
            ]);
            $times = $data['times'];
            unset($data['times']);
            $point = $this->pointService->createPoint($data);
            foreach ($times as $time) {
                $this->pointService->createTime($point->id, $time);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('api.points.created'),
                'data' => new PointResource($point),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.points.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $point = Point::find($id);
            if($point === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.points.not_found'),
                ], 404);
            }
            $data = $request->validate([
                'name_en' => 'sometimes|required|string|max:255',
                'name_ar' => 'sometimes|required|string|max:255',
                'lat' => 'nullable|string|max:255',
                'long' => 'nullable|string|max:255',
                'route_id' => 'sometimes|required|exists:routes,id',
            ]);
            $point = $this->pointService->updatePoint($point->id, $data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.points.updated'),
                'data' => new PointResource($point),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.points.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $point = Point::find($id);
            if($point === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.points.not_found'),
                ], 404);
            }
            $point->delete();

            return response()->json([
                'status' => 'success',
                'message' => __('api.points.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.points.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // get points by route id
    public function getPointsByRouteId($routeId)
    {
        try {
            $route = Route::find($routeId);
            if($route === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.routes.not_found'),
                ], 404);
            }
            $points = $route->points()->with('times')->get();
            return response()->json([
                'status' => 'success',
                'message' => __('api.points.list_retrieved'),
                'data' => PointResource::collection($points),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.points.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
