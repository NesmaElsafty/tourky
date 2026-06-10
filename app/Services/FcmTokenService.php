<?php

namespace App\Services;

use App\Models\User;

class FcmTokenService
{
    public function __construct(private FcmNotificationService $fcmNotificationService) {}

    public function updateToken(User $user, string $fcmToken): User
    {
        $user->forceFill(['fcm_token' => $fcmToken])->save();

        return $user->fresh();
    }

    public function clearToken(User $user): User
    {
        $user->forceFill(['fcm_token' => null])->save();

        return $user->fresh();
    }

    /**
     * @return array{sent: bool, message: string, error: string|null}
     */
    public function sendTest(User $user, string $title, string $body): array
    {
        if (! $this->fcmNotificationService->isConfigured()) {
            return [
                'sent' => false,
                'message' => __('api.fcm.not_configured'),
                'error' => 'firebase_credentials_missing',
            ];
        }

        if (! $user->hasFcmToken()) {
            return [
                'sent' => false,
                'message' => __('api.fcm.token_missing'),
                'error' => 'fcm_token_missing',
            ];
        }

        try {
            $factory = new \Kreait\Firebase\Factory;
            $messaging = $factory
                ->withServiceAccount(config('services.firebase.credentials'))
                ->createMessaging();

            $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(\Kreait\Firebase\Messaging\Notification::create($title, $body))
                ->withData(['type' => 'test']);

            $messaging->send($message);
        } catch (\Throwable $e) {
            return [
                'sent' => false,
                'message' => __('api.fcm.test_failed'),
                'error' => $e->getMessage(),
            ];
        }

        return [
            'sent' => true,
            'message' => __('api.fcm.test_sent'),
            'error' => null,
        ];
    }
}
