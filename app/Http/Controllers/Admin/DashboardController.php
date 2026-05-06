<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function __construct(
        private AdminDashboardService $dashboardService,
    ) {}

    public function index(Request $request)
    {
        try {
            $data = $request->validate([
                'chart_date' => ['nullable', 'date'],
            ]);

            $payload = $this->dashboardService->getOverview($data['chart_date'] ?? null);

            return response()->json([
                'status' => 'success',
                'message' => __('api.dashboard.retrieved'),
                'data' => $payload,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.dashboard.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
