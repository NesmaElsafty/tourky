<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserResource;
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
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,NULL,id,type,admin'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $result = $this->authService->register($data, 'admin');

        return response()->json([
            'message' => 'Admin registered successfully.',
            'user' => new AdminUserResource($result['user']),
            'token' => $result['token'],
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->login($credentials, 'admin');

        return response()->json([
            'message' => 'Admin logged in successfully.',
            'user' => new AdminUserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function profile(Request $request): AdminUserResource
    {
        return new AdminUserResource($this->authService->profile($request));
    }

    public function updateProfile(Request $request): AdminUserResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20', 'unique:users,phone,'.$request->user()->id.',id,type,admin'],
            'password' => ['sometimes', 'required', 'string', 'min:6', 'confirmed'],
        ]);

        return new AdminUserResource($this->authService->updateProfile($request, $data));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return response()->json([
            'message' => 'Admin logged out successfully.',
        ]);
    }
}
