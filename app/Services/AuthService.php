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

    public function updateProfile(Request $request, array $data): User
    {
        /** @var User $user */
        $user = $request->user();

        $user->update($data);

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
