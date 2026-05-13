<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Services\AuthService;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly PasswordResetOtpService $passwordResetOtpService,
    ) {}

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
            'user' => new AdminResource($result['user']),
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
            'user' => new AdminResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function profile(Request $request)
    {
        $user = $this->authService->profile($request);
        $this->applyLocale($request, $user);

        return response()->json([
            'message' => __('api.admin.profile_retrieved'),
            'user' => new AdminResource($user),
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
            'user' => new AdminResource($updatedUser),
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

    public function forgotPassword(Request $request)
    {
        $this->applyLocale($request);
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->passwordResetOtpService->sendOtp($request->string('email')->toString(), 'admin');

        return response()->json([
            'message' => __('api.password_reset.otp_sent'),
        ]);
    }

    public function verifyForgotPasswordOtp(Request $request)
    {
        $this->applyLocale($request);
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'regex:/^[0-9]{4,8}$/'],
        ]);

        $result = $this->passwordResetOtpService->verifyOtp($data['email'], 'admin', $data['otp']);

        if (! $result['ok']) {
            $message = match ($result['reason']) {
                'locked' => __('api.password_reset.locked_otp'),
                'expired' => __('api.password_reset.expired_otp'),
                default => __('api.password_reset.invalid_otp'),
            };

            return response()->json(['message' => $message], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => __('api.password_reset.otp_verified'),
            'reset_token' => $result['reset_token'],
        ]);
    }

    public function resetPasswordWithToken(Request $request)
    {
        $this->applyLocale($request);
        $data = $request->validate([
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! $this->passwordResetOtpService->resetPassword($data['reset_token'], $data['password'])) {
            return response()->json([
                'message' => __('api.password_reset.invalid_reset_token'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => __('api.password_reset.password_reset'),
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
