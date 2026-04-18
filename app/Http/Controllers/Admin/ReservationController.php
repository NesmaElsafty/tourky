<?php

namespace App\Http\Controllers\Admin;

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
            $reservations = $this->reservationService->getAdminReservationsPaginated(
                (int) ($request->per_page ?? 10),
            );
            $pagination = PaginationHelper::paginate($reservations);

            return response()->json([
                'status' => 'success',
                'message' => __('api.reservations.admin_list_retrieved'),
                'data' => ReservationResource::collection($reservations),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.reservations.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, Reservation $reservation)
    {
        try {
            $data = $request->validate([
                'status' => 'required|in:confirmed,cancelled',
            ], [
                'status.required' => __('api.reservations.validation_status_required'),
                'status.in' => __('api.reservations.validation_status_in'),
            ]);
            $reservation = $this->reservationService->updateReservationStatus(
                $reservation,
                $data['status'],
            );

            return response()->json([
                'status' => 'success',
                'message' => __('api.reservations.admin_status_updated'),
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
}
