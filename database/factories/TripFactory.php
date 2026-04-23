<?php

namespace Database\Factories;

use App\Models\Time;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    protected $model = Trip::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $time = Time::query()
            ->whereHas('point', function ($query): void {
                $query->whereHas('route', fn ($routeQuery) => $routeQuery->where('type', 'b2c'));
            })
            ->inRandomOrder()
            ->first();

        return [
            'time_id' => $time?->id ?? Time::factory(),
            'date' => fake()->dateTimeBetween('+2 days', '+30 days')->format('Y-m-d'),
            'status' => fake()->randomElement(['planned', 'in_progress']),
        ];
    }

    public function planned(): static
    {
        return $this->state(fn (): array => [
            'status' => 'planned',
        ]);
    }
}
