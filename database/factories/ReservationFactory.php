<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Time;
use App\Models\User;
use Carbon\Carbon;
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

    /**
     * Client-rated captain after a completed ride segment: pickup/drop-off timestamps + stars + optional feedback.
     * Use together with attributes that assign the reservation to a trip and vehicle (`trip_id`, `trip_car_id`).
     */
    public function withCaptainRating(?int $rating = null, ?string $feedback = null): static
    {
        return $this->state(function () use ($rating, $feedback): array {
            $droppedOff = Carbon::now()->subMinutes(fake()->numberBetween(30, 60 * 24 * 45));
            $pickedUp = (clone $droppedOff)->subMinutes(fake()->numberBetween(10, 180));

            return [
                'picked_up_at' => $pickedUp,
                'dropped_off_at' => $droppedOff,
                'captain_rating' => $rating ?? fake()->numberBetween(1, 5),
                'captain_feedback' => $feedback ?? fake()->optional(0.65)->paragraph(),
            ];
        });
    }
}
