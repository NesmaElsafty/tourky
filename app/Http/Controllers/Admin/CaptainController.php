<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaptainResource;
use App\Services\CaptainService;
use Illuminate\Http\Request;
use App\Helpers\PaginationHelper;
class CaptainController extends Controller
{
    public function __construct(private CaptainService $captainService)
    {
        $this->captainService = $captainService;
    }

    public function index(Request $request)
    {
        try {
            $captains = $this->captainService->getAllCaptains()->paginate($request->per_page ?? 10);
            $pagination = PaginationHelper::paginate($captains);
            
            return response()->json([
                'status' => 'success',
                'message' => __('api.captains.list_retrieved'),
                'data' => CaptainResource::collection($captains),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captains.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $captain = $this->captainService->getCaptainById($id);
            return response()->json([
                'status' => 'success',
                'message' => __('api.captains.retrieved'),
                'data' => new CaptainResource($captain),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captains.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:255|unique:users,phone',
                'password' => 'required|string|min:6|confirmed',
            ]);
            $captain = $this->captainService->createCaptain($data);
            return response()->json([
                'status' => 'success',
                'message' => __('api.captains.created'),
                'data' => new CaptainResource($captain),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captains.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $captain = $this->captainService->updateCaptain($request, $id);
            return response()->json([
                'status' => 'success',
                'message' => __('api.captains.updated'),
                'data' => new CaptainResource($updatedCaptain),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captains.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $this->captainService->deleteCaptain($id);
            return response()->json([
                'status' => 'success',
                'message' => __('api.captains.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captains.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
