<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\Trip;
use App\Models\TripCar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripCar>
 */
class TripCarFactory extends Factory
{
    protected $model = TripCar::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $captain = User::query()
            ->where('type', 'captain')
            ->inRandomOrder()
            ->first();

        $car = Car::query()->inRandomOrder()->first();

        return [
            'trip_id' => Trip::factory(),
            'car_id' => $car?->id ?? Car::factory(),
            'captain_id' => $captain?->id ?? User::factory()->state([
                'type' => 'captain',
                'role_id' => null,
            ]),
        ];
    }
}
