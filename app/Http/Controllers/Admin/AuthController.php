<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserResource;
use App\Services\AuthService;
use App\Support\ApiLocale;
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

        ApiLocale::applyFromUserLanguage($result['user']);

        return response()->json([
            'message' => __('api.admin.registered'),
            'user' => new AdminUserResource($result['user']),
            'token' => $result['token'],
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request)
    {
        ApiLocale::apply(ApiLocale::fromRequest($request));

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

        ApiLocale::applyFromUserLanguage($result['user']);

        return response()->json([
            'message' => __('api.admin.logged_in'),
            'user' => new AdminUserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function profile(Request $request)
    {
        $user = $this->authService->profile($request);

        return response()->json([
            'message' => __('api.admin.profile_retrieved'),
            'user' => new AdminUserResource($user),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $updatedUser = $this->authService->updateProfile($request);

        if ($request->hasFile('image')) {
            $updatedUser->addMedia($request->file('image'))->toMediaCollection('avatar');
            $updatedUser = $updatedUser->fresh();
        }

        return response()->json([
            'message' => __('api.admin.update_profile_success'),
            'user' => new AdminUserResource($updatedUser),
        ], 200);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request);

        return response()->json([
            'message' => __('api.admin.logged_out'),
        ]);
    }
}
