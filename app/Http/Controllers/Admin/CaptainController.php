<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCaptainRequest;
use App\Http\Resources\CaptainResource;
use App\Services\CaptainRatingService;
use App\Services\CaptainService;
use Illuminate\Http\Request;

class CaptainController extends Controller
{
    public function __construct(
        private CaptainService $captainService,
        private CaptainRatingService $captainRatingService,
    ) {}

    public function index(Request $request)
    {
        try {
            $captains = $this->captainService->getAllCaptains()->paginate($request->per_page ?? 10);
            $this->captainRatingService->aggregateForCaptainIds(
                $captains->pluck('id')->map(fn ($id) => (int) $id)->all()
            );
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
            $this->captainRatingService->aggregateForCaptainIds([(int) $captain->id]);
            $captain->load([
                'receivedFeedbacks' => fn ($q) => $q->with('client:id,name')->latest()->limit(50),
            ]);
            $captain->setAttribute(
                'captain_feedback_entries',
                $this->captainRatingService->feedbackEntriesForCaptain((int) $captain->id),
            );
            $captain->setAttribute(
                'captain_report_entries',
                $this->captainRatingService->reportEntriesForCaptain((int) $captain->id),
            );
            $captain->load('car');

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

    // get captain by phone number
    public function getCaptainByPhoneNumber($phone)
    {
        try {
            $captain = $this->captainService->getCaptainByPhoneNumber($phone);
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


    public function store(StoreCaptainRequest $request)
    {
        try {
            $data = $request->validated();
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
            $this->captainRatingService->aggregateForCaptainIds([(int) $captain->id]);
            $captain->load([
                'receivedFeedbacks' => fn ($q) => $q->with('client:id,name')->latest()->limit(50),
            ]);
            $captain->setAttribute(
                'captain_feedback_entries',
                $this->captainRatingService->feedbackEntriesForCaptain((int) $captain->id),
            );
            $captain->setAttribute(
                'captain_report_entries',
                $this->captainRatingService->reportEntriesForCaptain((int) $captain->id),
            );

            return response()->json([
                'status' => 'success',
                'message' => __('api.captains.updated'),
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
