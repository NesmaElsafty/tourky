<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FcmNotificationService;
use Illuminate\Console\Command;

class TestFcmNotification extends Command
{
    protected $signature = 'notification:test-fcm
                            {user : User id, phone, or email}
                            {--token= : Set FCM token on the user before sending}
                            {--title=Test notification : Notification title}
                            {--body=This is a test push from Tourky. : Notification body}';

    protected $description = 'Send a test FCM push to a user who has an fcm_token';

    public function handle(FcmNotificationService $fcmService): int
    {
        if (! $fcmService->isConfigured()) {
            $this->error('Firebase credentials not found. Set FIREBASE_CREDENTIALS in .env (default: firebase/firebase.json).');

            return self::FAILURE;
        }

        $identifier = (string) $this->argument('user');

        $user = User::query()
            ->where('id', $identifier)
            ->orWhere('phone', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if ($user === null) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        if ($token = $this->option('token')) {
            $user->forceFill(['fcm_token' => $token])->save();
            $this->info('Saved FCM token on user #'.$user->id);
        }

        if (! $user->hasFcmToken()) {
            $this->error('User #'.$user->id.' has no fcm_token. Pass --token=YOUR_DEVICE_TOKEN or update profile with fcm_token.');

            return self::FAILURE;
        }

        $title = (string) $this->option('title');
        $body = (string) $this->option('body');

        $this->info('Sending to user #'.$user->id.' ('.$user->type.')…');

        try {
            $factory = \Kreait\Firebase\Factory::class;
            $messaging = (new $factory)
                ->withServiceAccount(config('services.firebase.credentials'))
                ->createMessaging();

            $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(\Kreait\Firebase\Messaging\Notification::create($title, $body))
                ->withData(['type' => 'test']);

            $messaging->send($message);
        } catch (\Throwable $e) {
            $this->error('FCM send failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Push notification sent successfully.');

        return self::SUCCESS;
    }
}
