<?php

namespace App\Http\Controllers\Captain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Captain\TrackingLocationRequest;
use App\Models\Trip;
use App\Models\TripCar;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class TrackingController extends Controller
{
    public function updateLocation(TrackingLocationRequest $request, Trip $trip)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validated();

        $isAssigned = TripCar::query()
            ->where('trip_id', $trip->id)
            ->where('captain_id', $user->id)
            ->exists();

        if (! $isAssigned) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captain_trips.not_assigned'),
            ], Response::HTTP_FORBIDDEN);
        }

        if ($trip->status !== 'in_progress') {
            return response()->json([
                'status' => 'error',
                'message' => __('api.tracking.trip_not_in_progress'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $lat = round((float) $validated['lat'], 6);
        $long = round((float) $validated['long'], 6);

        $user->update([
            'lat' => $lat,
            'long' => $long,
        ]);

        $trackingServiceUrl = rtrim((string) config('services.tracking.base_url'), '/');
        $trackingSecret = (string) config('services.tracking.internal_secret');

        if ($trackingServiceUrl === '' || $trackingSecret === '') {
            return response()->json([
                'status' => 'error',
                'message' => __('api.tracking.config_missing'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $trackingResponse = Http::timeout(3)
            ->acceptJson()
            ->withHeaders(['X-Tracking-Secret' => $trackingSecret])
            ->post($trackingServiceUrl.'/internal/emit-loc', [
                'trip_id' => $trip->id,
                'user_id' => $user->id,
                'la' => $lat,
                'lo' => $long,
            ]);

        if (! $trackingResponse->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.tracking.unavailable'),
            ], Response::HTTP_BAD_GATEWAY);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('api.tracking.location_updated'),
            'data' => [
                'trip_id' => $trip->id,
                'captain_id' => $user->id,
                'lat' => $lat,
                'long' => $long,
            ],
        ]);
    }
}
