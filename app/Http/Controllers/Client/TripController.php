<?php

namespace App\Http\Controllers\Client;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\RateCaptainRequest;
use App\Http\Requests\Client\TripIndexRequest;
use App\Http\Resources\ClientTripResource;
use App\Models\Reservation;
use App\Services\CaptainRatingService;
use App\Services\ClientTripService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TripController extends Controller
{
    public function __construct(
        private ClientTripService $clientTripService,
        private CaptainRatingService $captainRatingService,
    ) {}

    public function index(TripIndexRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $scope = $request->filled('scope')
                ? $request->string('scope')->toString()
                : 'history';
            $perPage = (int) ($request->input('per_page', 10));

            if ($scope === 'today') {
                $trips = $this->clientTripService->getTodayTripsForClient($user);
                $this->captainRatingService->primeForReservations($trips);

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

            if ($paginator instanceof PaginationLengthAwarePaginator) {
                $this->captainRatingService->primeForReservations(collect($paginator->items()));
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
            /** @var \App\Models\User $user */
            $user = $request->user();

            $trip = $this->clientTripService->getTripDetailForClient($user, $reservation);

            if ($trip->tripCar?->captain_id !== null) {
                $this->captainRatingService->aggregateForCaptainId((int) $trip->tripCar->captain_id);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('api.trips.client_detail_retrieved'),
                'data' => new ClientTripResource($trip),
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function rateCaptain(RateCaptainRequest $request, Reservation $reservation)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $rating = (int) $request->input('rating');
            $feedbackRaw = $request->input('feedback');
            $feedback = is_string($feedbackRaw) && trim($feedbackRaw) !== ''
                ? mb_substr(trim($feedbackRaw), 0, 2000)
                : null;

            $trip = $this->clientTripService->submitCaptainRating($user, $reservation, $rating, $feedback);

            if ($trip->tripCar?->captain_id !== null) {
                $this->captainRatingService->invalidateCaptain((int) $trip->tripCar->captain_id);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('api.trips.captain_rating_saved'),
                'data' => new ClientTripResource($trip),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
