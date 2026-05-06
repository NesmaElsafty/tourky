<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('01#########'),
            'email' => fake()->boolean(35) ? null : fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'language' => fake()->randomElement(['en', 'ar']),
            'type' => fake()->randomElement(['admin', 'captain', 'client']),
            'role_id' => null,
            'company_id' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Captain portal user with availability defaults (DB columns are nullable / defaulted for other types).
     */
    public function captain(): static
    {
        return $this->state(fn (): array => [
            'type' => 'captain',
            'role_id' => null,
            'status' => 'available',
            'has_trip' => false,
            'trip_id' => null,
        ]);
    }
}
