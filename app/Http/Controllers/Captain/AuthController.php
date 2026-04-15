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
        try {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,NULL,id,type,captain'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $result = $this->authService->register($data, 'captain');

        $this->applyLocale($request, $result['user']);

        return response()->json([
            'message' => __('api.captain.registered'),
            'user' => new CaptainUserResource($result['user']),
            'token' => $result['token'],
        ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('api.captain.registration_failed'),
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

    }

    public function login(Request $request): JsonResponse
    {
        $this->applyLocale($request);

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

        $this->applyLocale($request, $result['user']);

        return response()->json([
            'message' => __('api.captain.logged_in'),
            'user' => new CaptainUserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function profile(Request $request): CaptainUserResource
    {
        try {
            $user = $this->authService->profile($request);
            $this->applyLocale($request, $user);

            return new CaptainUserResource($user);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('api.captain.profile_failed'),
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function updateProfile(Request $request): CaptainUserResource
    {
        try {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20', 'unique:users,phone,'.$request->user()->id.',id,type,captain'],
            'password' => ['sometimes', 'required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $this->authService->updateProfile($request, $data);
        $this->applyLocale($request, $user);

        return new CaptainUserResource($user);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('api.captain.update_profile_failed'),
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
        $this->applyLocale($request, $request->user());
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
