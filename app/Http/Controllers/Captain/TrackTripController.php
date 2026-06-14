<?php

namespace App\Http\Controllers\Captain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Captain\StoreTrackTripRequest;
use App\Http\Resources\CaptainTrackTripResource;
use App\Services\TrackTripService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TrackTripController extends Controller
{
    public function __construct(
        private TrackTripService $trackTripService,
    ) {}

    public function store(StoreTrackTripRequest $request)
    {
        try {
            /** @var \App\Models\User $captain */
            $captain = $request->user();

            $trackTrip = $this->trackTripService->storeTrackTripForCaptain(
                $captain,
                $request->validated(),
            );

            return response()->json([
                'status' => 'success',
                'message' => __('api.track_trips.stored'),
                'data' => new CaptainTrackTripResource($trackTrip),
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.track_trips.server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
