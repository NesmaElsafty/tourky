<?php

namespace App\Http\Controllers\Captain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Captain\CaptainOnlineToggleRequest;
use App\Http\Requests\Captain\UpdateCaptainProfileRequest;
use App\Http\Resources\CaptainResource;
use App\Services\AuthService;
use App\Services\FcmTokenService;
use App\Services\CaptainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly CaptainService $captainService,
        private readonly FcmTokenService $fcmTokenService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $result = $this->authService->login($credentials, 'captain');

        if ($result === null) {
            return response()->json([
                'message' => __('api.auth.wrong_credentials'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'message' => __('api.captain.logged_in'),
            'data' => new CaptainResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function profile(Request $request)
    {
        try {
            $user = $this->authService->profile($request);
            if ($user->type === 'captain') {
                $user->load([
                    'receivedFeedbacks' => fn ($q) => $q->with('client:id,name')->latest()->limit(50),
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('api.captain.profile_retrieved'),
                'data' => new CaptainResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('api.captain.profile_failed'),
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function updateProfile(UpdateCaptainProfileRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->updateProfile($request, $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => __('api.captain.profile_updated'),
                'data' => new CaptainResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captain.update_profile_failed'),
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request);

            return response()->json([
                'message' => __('api.captain.logged_out'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('api.captain.logout_failed'),
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function isOnlineToggle(CaptainOnlineToggleRequest $request)
    {
        $user = auth()->user();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $user->addMediaFromRequest('image')
                ->toMediaCollection('image');
        }
        $captain = $this->captainService->isOnlineToggle($user->id);
        return response()->json([
            'message' => $captain->is_online ? __('api.captain.is_online') : __('api.captain.is_offline'),
            'data' => new CaptainResource($captain),
        ]);
    }

    // captain update balance
    public function updateBalance(Request $request)
    {
        try {
            $data = $request->validate([
                'amount' => 'required|numeric',
            ]);
            $user = auth()->user();
            $user->balance += $data['amount'];
            $user->save();
            return response()->json([
                'status' => 'success',
                'message' => __('api.captain.balance_updated'),
                'data' => new CaptainResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.captain.balance_update_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
