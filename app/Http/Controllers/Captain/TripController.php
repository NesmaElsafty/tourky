<?php

namespace App\Http\Controllers\Captain;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Captain\TripIndexRequest;
use App\Http\Resources\CaptainTripDetailResource;
use App\Http\Resources\CaptainTripListResource;
use App\Models\Reservation;
use App\Models\Trip;
use App\Services\CaptainTripService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TripController extends Controller
{
    public function __construct(private CaptainTripService $captainTripService) {}

    public function index(TripIndexRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $scope = $request->filled('scope')
                ? $request->string('scope')->toString()
                : 'history';

            if ($scope === 'today') {
                $trips = $this->captainTripService->getTodayTripsForCaptain($user);

                return response()->json([
                    'status' => 'success',
                    'message' => __('api.captain_trips.today_retrieved'),
                    'data' => CaptainTripListResource::collection($trips),
                ]);
            }

            $paginator = $this->captainTripService->getTripsForCaptain(
                $user,
                (int) ($request->input('per_page', 10)),
                $scope,
            );

            $message = $scope === 'upcoming'
                ? __('api.trips.client_upcoming_retrieved')
                : __('api.trips.client_history_retrieved');

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => CaptainTripListResource::collection($paginator),
                'pagination' => PaginationHelper::paginate($paginator),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captain_trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, Trip $trip)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $trip = $this->captainTripService->getTripForCaptain($user, $trip);

            return response()->json([
                'status' => 'success',
                'message' => __('api.captain_trips.detail_retrieved'),
                'data' => new CaptainTripDetailResource($trip),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captain_trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    

    public function start(Request $request, Trip $trip)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $updated = $this->captainTripService->startTripForCaptain($user, $trip);

            return response()->json([
                'status' => 'success',
                'message' => __('api.captain_trips.started'),
                'data' => new CaptainTripDetailResource(
                    $this->captainTripService->getTripForCaptain($user, $updated)
                ),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captain_trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function confirmPickup(Request $request, Trip $trip, Reservation $reservation)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $updated = $this->captainTripService->confirmClientPickup($user, $trip, $reservation);

            return response()->json([
                'status' => 'success',
                'message' => __('api.captain_trips.arrival_confirmed'),
                'data' => [
                    'reservation_id' => $updated->id,
                    'picked_up_at' => $updated->picked_up_at?->toIso8601String(),
                    'dropped_off_at' => $updated->dropped_off_at?->toIso8601String(),
                    'client' => [
                        'id' => $updated->user?->id,
                        'name' => $updated->user?->name,
                        'phone' => $updated->user?->phone,
                    ],
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captain_trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function confirmDropoff(Request $request, Trip $trip, Reservation $reservation)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $updated = $this->captainTripService->confirmClientDropoff($user, $trip, $reservation);

            return response()->json([
                'status' => 'success',
                'message' => __('api.captain_trips.dropoff_confirmed'),
                'data' => [
                    'reservation_id' => $updated->id,
                    'picked_up_at' => $updated->picked_up_at?->toIso8601String(),
                    'dropped_off_at' => $updated->dropped_off_at?->toIso8601String(),
                    'client' => [
                        'id' => $updated->user?->id,
                        'name' => $updated->user?->name,
                        'phone' => $updated->user?->phone,
                    ],
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captain_trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function close(Request $request, Trip $trip)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $updated = $this->captainTripService->closeTripForCaptain($user, $trip);

            return response()->json([
                'status' => 'success',
                'message' => __('api.captain_trips.trip_closed'),
                'data' => new CaptainTripDetailResource(
                    $this->captainTripService->getTripForCaptain($user, $updated)
                ),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captain_trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
