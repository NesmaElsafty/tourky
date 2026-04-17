<?php

namespace Database\Factories;

use App\Models\Point;
use App\Models\Time;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Time>
 */
class TimeFactory extends Factory
{
    protected $model = Time::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pickup_time' => fake()->randomElement(['05:45', '06:00', '06:15', '06:30', '14:00', '14:30', '15:00']),
            'point_id' => Point::factory(),
            'is_active' => true,
        ];
    }
}
