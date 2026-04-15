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
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'email' => ['required', 'email', 'unique:users,email'],
        ]);
        $result = $this->authService->register($data, 'client');

        $this->applyLocale($request, $result['user']);

        return response()->json([
            'message' => __('api.client.registered'),
            'user' => new ClientUserResource($result['user']),
            'token' => $result['token'],
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request): JsonResponse
    {
        $this->applyLocale($request);

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

        $this->applyLocale($request, $result['user']);

        return response()->json([
            'message' => __('api.client.logged_in'),
            'user' => new ClientUserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function profile(Request $request): ClientUserResource
    {
        $user = $this->authService->profile($request);
        $this->applyLocale($request, $user);

        return new ClientUserResource($user);
    }

    public function updateProfile(Request $request): ClientUserResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20', 'unique:users,phone,'.$request->user()->id.',id,type,client'],
            'password' => ['sometimes', 'required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $this->authService->updateProfile($request, $data);
        $this->applyLocale($request, $user);

        return new ClientUserResource($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->applyLocale($request, $request->user());
        $this->authService->logout($request);

        return response()->json([
            'message' => __('api.client.logged_out'),
        ]);
    }

    private function applyLocale(Request $request, $user = null): string
    {
        $userLanguage = strtolower((string) ($user?->language ?? ''));
        if ($userLanguage === 'en' || $userLanguage === 'ar') {
            app()->setLocale($userLanguage);
            return $userLanguage;
        }

        $headerLanguage = strtolower((string) $request->header('lang', ''));
        $locale = $headerLanguage === 'ar' ? 'ar' : 'en';
        app()->setLocale($locale);

        return $locale;
    }
}
