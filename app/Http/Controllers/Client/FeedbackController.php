<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeedbackResource;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    public function __construct(private readonly FeedbackService $feedbackService) {}

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'captain_id' => ['required', 'integer', 'exists:users,id'],
                'feedback' => ['required', 'string', 'min:3', 'max:5000'],
                'rating' => ['required', 'integer', 'min:1', 'max:5'],
            ], [
                'captain_id.required' => __('api.feedbacks.validation_captain_required'),
                'feedback.required' => __('api.feedbacks.validation_feedback_required'),
                'rating.required' => __('api.feedbacks.validation_rating_required'),
            ]);

            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

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
