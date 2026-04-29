<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use App\Helpers\PaginationHelper;
class ReservationController extends Controller
{
    public function __construct(private ReservationService $reservationService) {}

    public function index(Request $request)
    {
        try {
            $groupedReservations = $this->reservationService->getPendingReservationsGroupedByDateAndTime();
            $perPage = (int) $request->input('per_page', 10);
            $perPage = max(1, $perPage);
            $currentPage = LengthAwarePaginator::resolveCurrentPage();

            $paginatedGroups = new LengthAwarePaginator(
                $groupedReservations->forPage($currentPage, $perPage)->values(),
                $groupedReservations->count(),
                $perPage,
                $currentPage
            );

            $pagination = PaginationHelper::paginate($paginatedGroups);
            $data = $paginatedGroups->getCollection()->map(
                fn (Collection $byTime): Collection => $byTime->map(
                    fn (Collection $reservations) => ReservationResource::collection($reservations)->resolve($request)
                    )
                );

            return response()->json([
                'status' => 'success',
                'message' => __('api.reservations.admin_list_retrieved'),
                'data' => $data,
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
