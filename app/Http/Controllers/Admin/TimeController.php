<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTimeRequest;
use App\Http\Requests\Admin\UpdateTimeRequest;
use App\Http\Resources\TimeResource;
use App\Models\Time;
use App\Services\TimeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimeController extends Controller
{
    public function __construct(private TimeService $timeService) {}

    public function index(Request $request)
    {
        try {
            $times = $this->timeService->getTimesPaginated(
                (int) ($request->per_page ?? 10),
                onlyActive: true,
            );
            $pagination = PaginationHelper::paginate($times);

            return response()->json([
                'status' => 'success',
                'message' => __('api.times.list_retrieved'),
                'data' => TimeResource::collection($times),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexAll(Request $request)
    {
        try {
            $times = $this->timeService->getTimesPaginated(
                (int) ($request->per_page ?? 10),
                onlyActive: false,
            );
            $pagination = PaginationHelper::paginate($times);

            return response()->json([
                'status' => 'success',
                'message' => __('api.times.list_retrieved'),
                'data' => TimeResource::collection($times),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $time = Time::query()->findOrFail($id);
            $time->loadMissing([
                'point:id,name_en,name_ar,route_id',
                'point.route:id,is_active',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('api.times.retrieved'),
                'data' => new TimeResource($time),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreTimeRequest $request)
    {
        try {
            $data = $request->validated();
            $time = $this->timeService->createTime($data);
            $time->load('point:id,name_en,name_ar,route_id');

            return response()->json([
                'status' => 'success',
                'message' => __('api.times.created'),
                'data' => new TimeResource($time),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateTimeRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $time = $this->timeService->updateTime($id, $data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.times.updated'),
                'data' => new TimeResource($time),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $time = Time::query()->findOrFail($id);
            $time->delete();

            return response()->json([
                'status' => 'success',
                'message' => __('api.times.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.times.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
