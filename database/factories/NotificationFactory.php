<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userType = fake()->randomElement(Notification::USER_TYPES);

        return [
            'title_en' => fake()->sentence(4),
            'title_ar' => 'إشعار '.$userType.' '.fake()->numerify('###'),
            'description_en' => fake()->paragraphs(2, true),
            'description_ar' => 'تفاصيل الإشعار بالعربية. '.fake()->text(400),
            'user_type' => $userType,
        ];
    }

    public function forClient(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_type' => 'client',
        ]);
    }

    public function forCaptain(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_type' => 'captain',
        ]);
    }
}
