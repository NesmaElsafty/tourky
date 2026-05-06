<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationDelivery>
 */
class NotificationDeliveryFactory extends Factory
{
    protected $model = NotificationDelivery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $notification = Notification::query()->inRandomOrder()->first();
        if ($notification !== null) {
            $user = User::query()
                ->where('type', $notification->user_type)
                ->inRandomOrder()
                ->first();

            if ($user === null) {
                $userFactory = User::factory();
                if ($notification->user_type === 'captain') {
                    $userFactory = $userFactory->captain();
                }
                $user = $userFactory->create([
                    'type' => $notification->user_type,
                    'role_id' => null,
                ]);
            }

            return [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
            ];
        }

        $userType = fake()->randomElement(Notification::USER_TYPES);
        $userFactory = User::factory();
        if ($userType === 'captain') {
            $userFactory = $userFactory->captain();
        }

        return [
            'notification_id' => Notification::factory()->state([
                'user_type' => $userType,
            ]),
            'user_id' => $userFactory->state([
                'type' => $userType,
                'role_id' => null,
            ]),
        ];
    }

    public function forClientPortal(): static
    {
        return $this->state(function (array $attributes): array {
            $notification = Notification::query()
                ->where('user_type', 'client')
                ->inRandomOrder()
                ->first()
                ?? Notification::factory()->forClient()->create();

            $user = User::query()
                ->where('type', 'client')
                ->inRandomOrder()
                ->first()
                ?? User::factory()->create(['type' => 'client', 'role_id' => null]);

            return [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
            ];
        });
    }

    public function forCaptainPortal(): static
    {
        return $this->state(function (array $attributes): array {
            $notification = Notification::query()
                ->where('user_type', 'captain')
                ->inRandomOrder()
                ->first()
                ?? Notification::factory()->forCaptain()->create();

            $user = User::query()
                ->where('type', 'captain')
                ->inRandomOrder()
                ->first()
                ?? User::factory()->captain()->create();

            return [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
            ];
        });
    }

    public function forPair(Notification $notification, User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
        ]);
    }
}
