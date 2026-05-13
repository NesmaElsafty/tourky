<?php

namespace App\Services;

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetOtpService
{
    private const ALLOWED_TYPES = ['admin', 'client'];

    public function sendOtp(string $email, string $type): void
    {
        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            return;
        }

        $normalized = $this->normalizeEmail($email);
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->where('type', $type)
            ->whereNotNull('email')
            ->first();

        if ($user === null) {
            return;
        }

        $length = max(4, min(8, (int) config('password_reset_otp.otp_length', 6)));
        $max = (10 ** $length) - 1;
        $otp = str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
        $ttl = now()->addMinutes((int) config('password_reset_otp.otp_ttl_minutes', 15));

        Cache::put($this->otpPayloadKey($type, $normalized), [
            'hash' => Hash::make($otp),
            'attempts' => 0,
        ], $ttl);

        $locale = in_array(strtolower((string) $user->language), ['ar', 'en'], true)
            ? strtolower((string) $user->language)
            : 'en';

        Mail::to($user->email)->send(new PasswordResetOtpMail($otp, $type, $locale));
    }

    /**
     * @return array{ok: true, reset_token: string}|array{ok: false, reason: 'invalid_otp'|'locked'|'expired'}
     */
    public function verifyOtp(string $email, string $type, string $otp): array
    {
        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            return ['ok' => false, 'reason' => 'invalid_otp'];
        }

        $normalized = $this->normalizeEmail($email);
        $key = $this->otpPayloadKey($type, $normalized);
        $payload = Cache::get($key);

        if (! is_array($payload) || ! isset($payload['hash'])) {
            return ['ok' => false, 'reason' => 'expired'];
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        $otpTtl = now()->addMinutes((int) config('password_reset_otp.otp_ttl_minutes', 15));

        if (! Hash::check($otp, $payload['hash'])) {
            $payload['attempts'] = $attempts + 1;
            if ($payload['attempts'] >= 5) {
                Cache::forget($key);

                return ['ok' => false, 'reason' => 'locked'];
            }
            Cache::put($key, $payload, $otpTtl);

            return ['ok' => false, 'reason' => 'invalid_otp'];
        }

        Cache::forget($key);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->where('type', $type)
            ->first();

        if ($user === null) {
            return ['ok' => false, 'reason' => 'expired'];
        }

        $resetToken = Str::random(64);
        $tokenTtl = now()->addMinutes((int) config('password_reset_otp.reset_token_ttl_minutes', 30));
        Cache::put($this->resetTokenKey($resetToken), $user->id, $tokenTtl);

        return ['ok' => true, 'reset_token' => $resetToken];
    }

    public function resetPassword(string $resetToken, string $password): bool
    {
        $key = $this->resetTokenKey($resetToken);
        $userId = Cache::pull($key);

        if ($userId === null) {
            return false;
        }

        $user = User::query()->find($userId);
        if ($user === null || ! in_array($user->type, self::ALLOWED_TYPES, true)) {
            return false;
        }

        $user->password = $password;
        $user->save();
        $user->tokens()->delete();

        return true;
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function otpPayloadKey(string $type, string $normalizedEmail): string
    {
        return 'password_reset_otp:'.$type.':'.$normalizedEmail;
    }

    private function resetTokenKey(string $token): string
    {
        return 'password_reset_token:'.$token;
    }
}
