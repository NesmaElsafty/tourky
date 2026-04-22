<?php

namespace Database\Factories;

use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    protected $model = Car::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['sedan', 'microbus']);
        $seats = $type === 'sedan' ? fake()->randomElement(['4', '5']) : fake()->randomElement(['12', '14', '16']);

        return [
            'name' => fake()->randomElement(['Toyota Corolla', 'Hyundai H1', 'Mercedes Sprinter', 'Nissan Sunny', 'Kia Carnival']).' '.fake()->numerify('###'),
            'number_of_seats' => $seats,
            'type' => $type,
            'plate_numbers' => fake()->numerify('####'),
            'plate_letters' => fake()->randomElement(['ABC', 'XYZ', 'RST', 'KLM']),
            'color' => fake()->safeColorName(),
            'status' => fake()->randomElement([
                'active', 'active', 'active', 'active',
                'inactive', 'maintenance', 'in_use',
            ]),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'maintenance',
        ]);
    }

    public function inUse(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'in_use',
        ]);
    }

    public function sedan(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'sedan',
            'number_of_seats' => fake()->randomElement(['4', '5']),
        ]);
    }

    public function microbus(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'microbus',
            'number_of_seats' => fake()->randomElement(['12', '14', '16']),
        ]);
    }
}
