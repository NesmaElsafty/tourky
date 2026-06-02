<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PointIndexRequest;
use App\Http\Requests\Admin\StorePointRequest;
use App\Http\Requests\Admin\UpdatePointRequest;
use App\Http\Resources\PointResource;
use App\Models\Point;
use App\Models\Route;
use App\Services\PointService;
use App\Services\TimeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PointController extends Controller
{
    public function __construct(private PointService $pointService, private TimeService $timeService) {}

    public function index(PointIndexRequest $request)
    {
        try {
            $points = $this->pointService->getPointsPaginated($request->route_id);
            $pagination = PaginationHelper::paginate($points);

            return response()->json([
                'status' => 'success',
                'message' => __('api.points.list_retrieved'),
                'data' => PointResource::collection($points),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
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
            $point = Point::query()->findOrFail($id);
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
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.points.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StorePointRequest $request)
    {
        try {
            $data = $request->validated();
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
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.points.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdatePointRequest $request, $id)
    {
        try {
            $point = Point::query()->findOrFail($id);
            $data = $request->validated();
            $point = $this->pointService->updatePoint($point->id, $data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.points.updated'),
                'data' => new PointResource($point),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
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
            $point = Point::query()->findOrFail($id);
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
            $route = Route::query()->findOrFail($routeId);
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
