<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TrackTripIndexRequest;
use App\Http\Resources\AdminTrackTripResource;
use App\Models\TrackTrip;
use App\Services\TrackTripService;
use Illuminate\Validation\ValidationException;

class TrackTripController extends Controller
{
    public function __construct(
        private TrackTripService $trackTripService,
    ) {}

    public function index(TrackTripIndexRequest $request)
    {
        try {
            $paginator = $this->trackTripService->paginateForAdmin($request);

            return response()->json([
                'status' => 'success',
                'message' => __('api.track_trips.admin_list_retrieved'),
                'data' => AdminTrackTripResource::collection($paginator),
                'pagination' => PaginationHelper::paginate($paginator),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.track_trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(TrackTrip $trackTrip)
    {
        try {
            $trackTrip = $this->trackTripService->findForAdmin((int) $trackTrip->id);

            return response()->json([
                'status' => 'success',
                'message' => __('api.track_trips.admin_retrieved'),
                'data' => new AdminTrackTripResource($trackTrip),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.track_trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
