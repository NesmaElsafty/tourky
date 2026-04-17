<?php

namespace App\Http\Controllers\Captain;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaptainResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

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
}
