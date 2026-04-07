<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ClientUserResource;
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
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,NULL,id,type,client'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $result = $this->authService->register($data, 'client');

        return response()->json([
            'message' => 'Client registered successfully.',
            'user' => new ClientUserResource($result['user']),
            'token' => $result['token'],
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->login($credentials, 'client');

        return response()->json([
            'message' => 'Client logged in successfully.',
            'user' => new ClientUserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function profile(Request $request): ClientUserResource
    {
        return new ClientUserResource($this->authService->profile($request));
    }

    public function updateProfile(Request $request): ClientUserResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20', 'unique:users,phone,'.$request->user()->id.',id,type,client'],
            'password' => ['sometimes', 'required', 'string', 'min:6', 'confirmed'],
        ]);

        return new ClientUserResource($this->authService->updateProfile($request, $data));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return response()->json([
            'message' => 'Client logged out successfully.',
        ]);
    }
}
