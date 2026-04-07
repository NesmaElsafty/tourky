<?php

namespace App\Http\Controllers\Captain;

use App\Http\Controllers\Controller;
use App\Http\Resources\Captain\CaptainUserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,NULL,id,type,captain'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $result = $this->authService->register($data, 'captain');

        return response()->json([
            'message' => 'Captain registered successfully.',
            'user' => new CaptainUserResource($result['user']),
            'token' => $result['token'],
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->login($credentials, 'captain');

        return response()->json([
            'message' => 'Captain logged in successfully.',
            'user' => new CaptainUserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function profile(Request $request): CaptainUserResource
    {
        return new CaptainUserResource($this->authService->profile($request));
    }

    public function updateProfile(Request $request): CaptainUserResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20', 'unique:users,phone,'.$request->user()->id.',id,type,captain'],
            'password' => ['sometimes', 'required', 'string', 'min:6', 'confirmed'],
        ]);

        return new CaptainUserResource($this->authService->updateProfile($request, $data));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return response()->json([
            'message' => 'Captain logged out successfully.',
        ]);
    }
}
