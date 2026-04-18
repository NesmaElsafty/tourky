<?php

namespace Database\Factories;

use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    protected $model = Term::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(Term::TYPES);
        $userType = fake()->randomElement(Term::USER_TYPES);

        return [
            'name_en' => ucfirst(str_replace('_', ' ', $type)).' ('.$userType.')',
            'name_ar' => 'بند '.$type.' ('.$userType.')',
            'description_en' => fake()->text(200),
            'description_ar' => fake()->text(200),
            'is_active' => true,
            'type' => $type,
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
