<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data, string $type): array
    {
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => $data['password'],
            'type' => $type,
        ]);

        $token = $user->createToken($this->tokenName($type))->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * @return array{user: User, token: string}|null
     */
    public function login(array $credentials, string $type): ?array
    {
        $user = User::where('phone', $credentials['phone'])
            ->where('type', $type)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        $token = $user->createToken($this->tokenName($type))->plainTextToken;

        return compact('user', 'token');
    }

    public function profile(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    /**
     * @param  array<string, mixed>|null  $data  Validated fields; omit for loose request-based updates (e.g. admin profile + media).
     */
    public function updateProfile(Request $request, ?array $data = null): User
    {
        /** @var User $user */
        $user = $request->user();

        if ($data !== null) {
            $user->update($data);
        } else {
            $user->name = $request->input('name', $user->name);
            $user->phone = $request->input('phone', $user->phone);
            $user->email = $request->input('email', $user->email);
            $user->language = $request->input('language', $user->language);
            if ($request->filled('password')) {
                $user->password = $request->input('password');
            }
            $user->save();
        }

        return $user->fresh();
    }

    public function logout(Request $request): void
    {
        $request->user()?->currentAccessToken()?->delete();
    }

    private function tokenName(string $type): string
    {
        return $type.'-token';
    }
}
