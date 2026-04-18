<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Time;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $time = Time::query()
            ->with('point')
            ->whereHas('point')
            ->inRandomOrder()
            ->first() ?? Time::factory()->create();

        $time->loadMissing('point');

        return [
            'user_id' => User::factory()->state([
                'type' => 'client',
                'role_id' => null,
            ]),
            'route_id' => $time->point->route_id,
            'point_id' => $time->point_id,
            'time_id' => $time->id,
            'date' => fake()->dateTimeBetween('+1 day', '+3 months')->format('Y-m-d'),
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled']),
        ];
    }

    /**
     * Use an existing schedule row; keeps route_id / point_id aligned with time_id.
     */
    public function forTime(Time $time): static
    {
        $time->loadMissing('point');

        return $this->state(function (array $attributes) use ($time): array {
            return [
                'route_id' => $time->point->route_id,
                'point_id' => $time->point_id,
                'time_id' => $time->id,
            ];
        });
    }
}
