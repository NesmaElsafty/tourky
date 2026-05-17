<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function __construct(private ReservationService $reservationService) {}

    public function index(Request $request)
    {
        try {
            $groupedReservations = $this->reservationService->getPendingReservationsGroupedByDateAndRouteTime();
            $totalReservationsCount = Reservation::query()
                ->where('status', 'pending')
                ->whereNotNull('route_time_id')
                ->count();

            $perPage = (int) $request->input('per_page', 10);
            $perPage = max(1, $perPage);
            $currentPage = LengthAwarePaginator::resolveCurrentPage();

            $pageSlice = $groupedReservations->forPage($currentPage, $perPage);

            $data = $pageSlice->map(function (Collection $byRouteTime, string $date) use ($request): array {
                $groups = $byRouteTime->map(function (Collection $reservations, string $routeTimeKey) use ($request): array {
                    return [
                        'route_time_id' => $routeTimeKey === '0' ? null : (int) $routeTimeKey,
                        'reservations_count' => $reservations->count(),
                        'reservations' => ReservationResource::collection($reservations)->resolve($request),
                    ];
                })->values();

                return [
                    'date' => $date,
                    'reservations_count' => $groups->sum('reservations_count'),
                    'groups' => $groups,
                ];
            })->values();

            $paginatedDates = new LengthAwarePaginator(
                $data,
                $groupedReservations->count(),
                $perPage,
                $currentPage
            );

            $pagination = PaginationHelper::paginate($paginatedDates);

            return response()->json([
                'status' => 'success',
                'message' => __('api.reservations.admin_list_retrieved'),
                'total_reservations_count' => $totalReservationsCount,
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

    public function groups(Request $request)
    {
        try {
            $request->validate([
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            $groups = $this->reservationService->getPendingReservationGroupSummariesPaginated(
                (int) ($request->per_page ?? 10)
            );
            $pagination = PaginationHelper::paginate($groups);

            $locale = strtolower((string) ($request->user()?->language ?? $request->header('lang', 'en')));
            $locale = $locale === 'ar' ? 'ar' : 'en';

            $data = collect($groups->items())
                ->map(static function (array $group) use ($locale): array {
                    $route = $group['route'];
                    $routeName = null;
                    if ($route !== null) {
                        $routeName = $locale === 'ar'
                            ? ($route->name_ar ?? $route->name_en)
                            : ($route->name_en ?? $route->name_ar);
                    }

                    return [
                        'date' => $group['date'],
                        'route_time_id' => $group['route_time_id'],
                        'route_id' => $group['route_id'],
                        'route_name' => $routeName,
                        'time_ids' => $group['time_ids'],
                        'reservations_count' => $group['reservations_count'],
                    ];
                })
                ->values();

            return response()->json([
                'status' => 'success',
                'message' => __('api.reservations.admin_groups_retrieved'),
                'data' => $data,
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

    public function calculatePrice(Request $request)
    {
        try {
            $data = $request->validate([
                'time_id' => 'required|integer|exists:times,id',
                'drop_off_time_id' => 'required|integer|exists:times,id',
            ], [
                'time_id.required' => __('api.reservations.validation_time_id'),
                'time_id.exists' => __('api.reservations.validation_time_id'),
                'drop_off_time_id.required' => __('api.reservations.validation_drop_off_time_id'),
                'drop_off_time_id.exists' => __('api.reservations.validation_drop_off_time_id'),
            ]);

            $price = $this->reservationService->calculatePriceForReservation($data['time_id'], $data['drop_off_time_id']);

            return response()->json([
                'status' => 'success',
                'message' => __('api.reservations.price_calculated'),
                'data' => $price,
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
