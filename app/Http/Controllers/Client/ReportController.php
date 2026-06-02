<?php

namespace App\Http\Controllers\Client;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ReportIndexRequest;
use App\Http\Requests\Client\StoreReportRequest;
use App\Http\Resources\ClientReportResource;
use App\Http\Resources\ClientTripResource;
use App\Models\CaptainReport;
use App\Models\Reservation;
use App\Services\ReportService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
    ) {}

    public function index(ReportIndexRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

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

    public function store(StoreReportRequest $request, Reservation $reservation)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

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

            return response()->json([
                'status' => 'success',
                'message' => __('api.reports.client_submitted'),
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
                'message' => __('api.reports.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
