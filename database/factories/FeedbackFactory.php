<?php

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => User::factory()->state(['type' => 'client', 'role_id' => null]),
            'captain_id' => User::factory()->captain(),
            'feedback' => fake()->paragraph(),
            'rating' => fake()->numberBetween(1, 5),
        ];
    }
}
