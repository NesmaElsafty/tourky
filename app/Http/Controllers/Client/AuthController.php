<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ClientUserResource;
use App\Services\AuthService;
use App\Support\ApiLocale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(Request $request): JsonResponse
    {
        dd($request->all());
       $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'email' => ['required', 'email', 'unique:users,email'],
        ]);
        $result = $this->authService->register($data, 'client');

        ApiLocale::applyFromUserLanguage($result['user']);

        return response()->json([
            'message' => __('api.client.registered'),
            'user' => new ClientUserResource($result['user']),
            'token' => $result['token'],
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request): JsonResponse
    {
        ApiLocale::apply(ApiLocale::fromRequest($request));

        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->login($credentials, 'client');

        if ($result === null) {
            return response()->json([
                'message' => __('api.auth.wrong_credentials'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        ApiLocale::applyFromUserLanguage($result['user']);

        return response()->json([
            'message' => __('api.client.logged_in'),
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
            'message' => __('api.client.logged_out'),
        ]);
    }
}
