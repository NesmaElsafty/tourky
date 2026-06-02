<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tracking\AuthorizeTripRequest;
use App\Models\Reservation;
use App\Models\TripCar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackingSocketAuthController extends Controller
{
    /** Minimal identity for the tracking microservice (Sanctum bearer token). Captain/client only. */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! in_array($user->type, ['captain', 'client'], true)) {
            return response()->json(['message' => __('api.auth.forbidden_permission')], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'id' => $user->id,
            'type' => $user->type,
        ]);
    }

    /**
     * Whether this user may subscribe to live updates for the given trip.
     */
    public function authorizeTrip(AuthorizeTripRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var \App\Models\User $user */
        $user = $request->user();

        $tripId = (int) $validated['trip_id'];

        if ($user->type === 'captain') {
            $allowed = TripCar::query()
                ->where('trip_id', $tripId)
                ->where('captain_id', $user->id)
                ->exists();

            return response()->json(['allowed' => $allowed]);
        }

        if ($user->type === 'client') {
            $allowed = Reservation::query()
                ->where('trip_id', $tripId)
                ->where('user_id', $user->id)
                ->exists();

            return response()->json(['allowed' => $allowed]);
        }

        return response()->json(['allowed' => false], Response::HTTP_FORBIDDEN);
    }
}
