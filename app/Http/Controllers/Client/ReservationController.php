<?php

namespace App\Http\Controllers\Client;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ReservationIndexRequest;
use App\Http\Requests\Client\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function __construct(private ReservationService $reservationService) {}

    public function index(ReservationIndexRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

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

    public function store(StoreReservationRequest $request)
    {
        try {
            $data = $request->validated();

            /** @var \App\Models\User $user */
            $user = $request->user();

            $reservation = $this->reservationService->createReservationForClient($user, [
                'time_id' => (int) $data['time_id'],
                'drop_off_time_id' => (int) $data['drop_off_time_id'],
                'date' => $data['date'],
            ]);

            $reservation->load(['route', 'point', 'time', 'dropOffTime']);

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
