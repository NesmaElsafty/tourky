<?php

namespace App\Http\Controllers\Client;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClientReportResource;
use App\Http\Resources\ClientTripResource;
use App\Models\CaptainReport;
use App\Models\Reservation;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
    ) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);

            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

            $perPage = (int) $request->input('per_page', 15);
            $paginator = $this->reportService->paginateReportsForClient($user, $perPage);

            return response()->json([
                'status' => 'success',
                'message' => __('api.reports.client_list_retrieved'),
                'data' => ClientReportResource::collection($paginator),
                'pagination' => PaginationHelper::paginate($paginator),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.reports.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request, Reservation $reservation)
    {
        try {
            $request->validate([
                'type' => ['required', 'in:trip,captain'],
                'message' => ['required', 'string', 'min:10', 'max:5000'],
            ], [
                'type.required' => __('api.reports.validation_type_required'),
                'type.in' => __('api.reports.validation_type_in'),
                'message.required' => __('api.reports.validation_message_required'),
                'message.min' => __('api.reports.validation_message_min'),
                'message.max' => __('api.reports.validation_message_max'),
            ]);

            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

            $type = $request->string('type')->toString();
            $messageRaw = $request->input('message');
            $message = is_string($messageRaw) ? trim($messageRaw) : '';
            if ($message === '') {
                throw ValidationException::withMessages([
                    'message' => [__('api.reports.validation_message_required')],
                ]);
            }

            $typeConst = $type === 'trip' ? CaptainReport::TYPE_TRIP : CaptainReport::TYPE_CAPTAIN;

            $trip = $this->reportService->submitClientReport($user, $reservation, $typeConst, mb_substr($message, 0, 5000));

            if ($trip === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.reservations.not_found'),
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('api.reports.client_submitted'),
                'data' => new ClientTripResource($trip),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.reports.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
