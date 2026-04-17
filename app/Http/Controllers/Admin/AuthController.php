<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,NULL,id,type,admin'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $result = $this->authService->register($data, 'admin');

        $this->applyLocale($request, $result['user']);

        return response()->json([
            'message' => __('api.admin.registered'),
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request)
    {
        $this->applyLocale($request);

        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->login($credentials, 'admin');

        if ($result === null) {
            return response()->json([
                'message' => __('api.auth.wrong_credentials'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $this->applyLocale($request, $result['user']);

        return response()->json([
            'message' => __('api.admin.logged_in'),
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function profile(Request $request)
    {
        $user = $this->authService->profile($request);
        $this->applyLocale($request, $user);

        return response()->json([
            'message' => __('api.admin.profile_retrieved'),
            'user' => new UserResource($user),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $updatedUser = $this->authService->updateProfile($request);
        $this->applyLocale($request, $updatedUser);

        if ($request->hasFile('image')) {
            $updatedUser->addMedia($request->file('image'))->toMediaCollection('avatar');
            $updatedUser = $updatedUser->fresh();
        }

        return response()->json([
            'message' => __('api.admin.update_profile_success'),
            'user' => new UserResource($updatedUser),
        ], 200);
    }

    public function logout(Request $request)
    {
        $this->applyLocale($request, $request->user());
        $this->authService->logout($request);

        return response()->json([
            'message' => __('api.admin.logged_out'),
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
