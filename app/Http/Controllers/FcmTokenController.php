<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shared\TestFcmNotificationRequest;
use App\Http\Requests\Shared\UpdateFcmTokenRequest;
use App\Services\FcmTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FcmTokenController extends Controller
{
    public function __construct(private FcmTokenService $fcmTokenService) {}

    public function update(UpdateFcmTokenRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $this->fcmTokenService->updateToken($user, $request->string('fcm_token')->toString());

        return response()->json([
            'status' => 'success',
            'message' => __('api.fcm.token_updated'),
            'data' => [
                'user_id' => $user->id,
                'has_fcm_token' => true,
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $this->fcmTokenService->clearToken($user);

        return response()->json([
            'status' => 'success',
            'message' => __('api.fcm.token_cleared'),
            'data' => [
                'user_id' => $user->id,
                'has_fcm_token' => false,
            ],
        ]);
    }

    public function test(TestFcmNotificationRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $result = $this->fcmTokenService->sendTest(
            $user,
            $request->input('title', __('api.fcm.test_title')),
            $request->input('body', __('api.fcm.test_body')),
        );

        return response()->json([
            'status' => $result['sent'] ? 'success' : 'error',
            'message' => $result['message'],
            'data' => [
                'user_id' => $user->id,
                'has_fcm_token' => $user->fresh()->hasFcmToken(),
                'sent' => $result['sent'],
                'error' => $result['error'],
            ],
        ], $result['sent'] ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
