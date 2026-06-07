<?php

namespace App\Http\Controllers\Client;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ReportIndexRequest;
use App\Http\Resources\ClientReportResource;
use App\Services\ReportService;
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
}
