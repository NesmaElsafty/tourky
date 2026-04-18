<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class FiredNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDeliveriesForPortal('client');
        $this->seedDeliveriesForPortal('captain');

        NotificationDelivery::factory()
            ->count(12)
            ->create();
    }

    /**
     * @param  'client'|'captain'  $userType
     */
    private function seedDeliveriesForPortal(string $userType): void
    {
        $notifications = Notification::query()->where('user_type', $userType)->get();
        $users = User::query()->where('type', $userType)->get();

        if ($notifications->isEmpty() || $users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            $count = fake()->numberBetween(1, min(4, $notifications->count()));
            $picked = $notifications->random($count);
            $items = $picked instanceof Collection ? $picked : collect([$picked]);
            foreach ($items as $notification) {
                NotificationDelivery::factory()
                    ->forPair($notification, $user)
                    ->create();
            }
        }
    }
}
