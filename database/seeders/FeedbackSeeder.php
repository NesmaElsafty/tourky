<?php

namespace Database\Seeders;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    private const ROWS = 18;

    public function run(): void
    {
        $captainIds = User::query()
            ->where('type', 'captain')
            ->pluck('id');

        $clientIds = User::query()
            ->where('type', 'client')
            ->pluck('id');

        if ($captainIds->isEmpty() || $clientIds->isEmpty()) {
            return;
        }

        for ($n = 0; $n < self::ROWS; $n++) {
            $captainId = (int) $captainIds->random();
            $clientPool = $clientIds->reject(fn (int $id): bool => $id === $captainId);
            if ($clientPool->isEmpty()) {
                break;
            }

            Feedback::query()->create([
                'client_id' => (int) $clientPool->random(),
                'captain_id' => $captainId,
                'feedback' => fake()->paragraph(),
                'rating' => fake()->numberBetween(1, 5),
            ]);
        }
    }
}
