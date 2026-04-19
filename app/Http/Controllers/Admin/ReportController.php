<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminReportResource;
use App\Models\CaptainReport;
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
            $paginator = $this->reportService->paginateReportsForAdmin($request);

            return response()->json([
                'status' => 'success',
                'message' => __('api.reports.admin_list_retrieved'),
                'data' => AdminReportResource::collection($paginator),
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

    public function show(Request $request, CaptainReport $report)
    {
        try {
            $report->load([
                'reservation.user:id,name,phone',
                'trip:id,date,status',
                'captain:id,name,phone',
                'repliedByUser:id,name',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('api.reports.admin_retrieved'),
                'data' => new AdminReportResource($report),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.reports.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reply(Request $request, CaptainReport $report)
    {
        try {
            $data = $request->validate([
                'admin_reply' => ['required', 'string', 'min:1', 'max:5000'],
            ], [
                'admin_reply.required' => __('api.reports.validation_reply_required'),
                'admin_reply.max' => __('api.reports.validation_reply_max'),
            ]);

            $admin = $request->user();
            if ($admin === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

            $reply = mb_substr(trim($data['admin_reply']), 0, 5000);

            $report = $this->reportService->replyAsAdmin($admin, $report, $reply);

            return response()->json([
                'status' => 'success',
                'message' => __('api.reports.admin_reply_saved'),
                'data' => new AdminReportResource($report),
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
