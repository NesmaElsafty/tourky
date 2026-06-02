<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreFeedbackRequest;
use App\Http\Resources\FeedbackResource;
use App\Services\FeedbackService;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    public function __construct(private readonly FeedbackService $feedbackService) {}

    public function store(StoreFeedbackRequest $request)
    {
        try {
            $data = $request->validated();

            /** @var \App\Models\User $user */
            $user = $request->user();

            $feedback = $this->feedbackService->createForClient($user, [
                'captain_id' => (int) $data['captain_id'],
                'feedback' => trim((string) $data['feedback']),
                'rating' => (int) $data['rating'],
            ]);

            $feedback->load(['captain:id,name']);

            return response()->json([
                'status' => 'success',
                'message' => __('api.feedbacks.created'),
                'data' => new FeedbackResource($feedback),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.feedbacks.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
