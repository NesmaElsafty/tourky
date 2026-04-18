<?php

namespace App\Http\Controllers\Client;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function __construct(private ReservationService $reservationService) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'scope' => 'required|in:upcoming,history',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ], [
                'scope.required' => __('api.reservations.validation_scope_required'),
                'scope.in' => __('api.reservations.validation_scope_invalid'),
            ]);

            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

            $reservations = $this->reservationService->getClientReservationsPaginated(
                $user,
                $request->string('scope')->toString(),
                (int) ($request->per_page ?? 10),
            );

            $pagination = PaginationHelper::paginate($reservations);

            $message = $request->string('scope')->toString() === 'upcoming'
                ? __('api.reservations.client_upcoming_retrieved')
                : __('api.reservations.client_history_retrieved');

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => ReservationResource::collection($reservations),
                'pagination' => $pagination,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.reservations.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'time_id' => 'required|integer|exists:times,id',
                'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            ], [
                'time_id.required' => __('api.reservations.validation_time_id'),
                'time_id.exists' => __('api.reservations.validation_time_id'),
                'date.required' => __('api.reservations.validation_date_past'),
                'date.date_format' => __('api.reservations.validation_date_past'),
                'date.after_or_equal' => __('api.reservations.validation_date_past'),
            ]);

            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

            $reservation = $this->reservationService->createReservationForClient($user, [
                'time_id' => (int) $data['time_id'],
                'date' => $data['date'],
            ]);

            $reservation->load(['route', 'point', 'time']);

            return response()->json([
                'status' => 'success',
                'message' => __('api.reservations.created'),
                'data' => new ReservationResource($reservation),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.reservations.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        try {
            if ($reservation->user_id !== $request->user()?->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.reservations.not_found'),
                ], 404);
            }

            $reservation = $this->reservationService->cancelReservationForClient($reservation);

            return response()->json([
                'status' => 'success',
                'message' => __('api.reservations.cancelled'),
                'data' => new ReservationResource($reservation),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.reservations.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, Reservation $reservation)
    {
        try {
            if ($reservation->user_id !== $request->user()?->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.reservations.not_found'),
                ], 404);
            }

            $this->reservationService->deleteReservationForClient($reservation);

            return response()->json([
                'status' => 'success',
                'message' => __('api.reservations.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.reservations.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
