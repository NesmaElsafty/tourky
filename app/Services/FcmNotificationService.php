<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class FcmNotificationService
{
    private ?Messaging $messaging = null;

    public function isConfigured(): bool
    {
        $path = config('services.firebase.credentials');

        return is_string($path) && $path !== '' && file_exists($path);
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (! filled($user->fcm_token)) {
            return false;
        }

        if (! $this->isConfigured()) {
            Log::warning('FCM credentials missing; skipped push for user '.$user->id);

            return false;
        }

        try {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(FcmNotification::create($title, $body))
                ->withData(array_map('strval', $data));

            $this->messaging()->send($message);

            return true;
        } catch (MessagingException $e) {
            if ($this->isInvalidTokenError($e)) {
                $user->forceFill(['fcm_token' => null])->save();
            }

            Log::warning('FCM send failed for user '.$user->id.': '.$e->getMessage());

            return false;
        }
    }

    /**
     * @param  Collection<int, User>|list<User>  $users
     */
    public function sendNotificationToUsers(Collection|array $users, Notification $notification): int
    {
        $sent = 0;

        foreach ($users as $user) {
            if ($this->sendNotificationToUser($user, $notification)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function sendNotificationToUser(User $user, Notification $notification): bool
    {
        [$title, $body] = $this->localizedContent($user, $notification);

        return $this->sendToUser($user, $title, $body, [
            'notification_id' => (string) $notification->id,
            'type' => $notification->user_type,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function localizedContent(User $user, Notification $notification): array
    {
        $locale = $user->language === 'ar' ? 'ar' : 'en';

        $title = $locale === 'ar'
            ? ($notification->title_ar ?? $notification->title_en ?? '')
            : ($notification->title_en ?? $notification->title_ar ?? '');

        $body = $locale === 'ar'
            ? ($notification->description_ar ?? $notification->description_en ?? '')
            : ($notification->description_en ?? $notification->description_ar ?? '');

        return [$title, $body];
    }

    private function messaging(): Messaging
    {
        if ($this->messaging === null) {
            $this->messaging = (new Factory)
                ->withServiceAccount(config('services.firebase.credentials'))
                ->createMessaging();
        }

        return $this->messaging;
    }

    private function isInvalidTokenError(MessagingException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'not-found')
            || str_contains($message, 'unregistered')
            || str_contains($message, 'invalid-argument');
    }
}
