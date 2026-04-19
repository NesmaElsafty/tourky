<?php

namespace App\Http\Controllers\Client;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClientTripResource;
use App\Models\Reservation;
use App\Services\ClientTripService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TripController extends Controller
{
    public function __construct(private ClientTripService $clientTripService) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'scope' => ['required', 'in:upcoming,history,today'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ], [
                'scope.required' => __('api.trips.client_validation_scope_required'),
                'scope.in' => __('api.trips.client_validation_scope_invalid'),
            ]);

            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

            $scope = $request->string('scope')->toString();
            $perPage = (int) ($request->input('per_page', 10));

            if ($scope === 'today') {
                $trips = $this->clientTripService->getTodayTripsForClient($user);

                return response()->json([
                    'status' => 'success',
                    'message' => __('api.trips.client_today_retrieved'),
                    'data' => ClientTripResource::collection($trips),
                ]);
            }

            if ($scope === 'upcoming') {
                $paginator = $this->clientTripService->getUpcomingTripsPaginated($user, $perPage);
                $message = __('api.trips.client_upcoming_retrieved');
            } else {
                $paginator = $this->clientTripService->getHistoryTripsPaginated($user, $perPage);
                $message = __('api.trips.client_history_retrieved');
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => ClientTripResource::collection($paginator),
                'pagination' => PaginationHelper::paginate($paginator),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, Reservation $reservation)
    {
        try {
            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

            $trip = $this->clientTripService->getTripDetailForClient($user, $reservation);

            if ($trip === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.reservations.not_found'),
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('api.trips.client_detail_retrieved'),
                'data' => new ClientTripResource($trip),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
