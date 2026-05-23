<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        $captainIds = User::query()
            ->where('type', 'captain')
            ->orderBy('id')
            ->pluck('id');

        $captainOffset = 0;

        Car::factory()->count(8)->active()->sequence(...$this->captainSequence(8, $captainIds, $captainOffset))->create();
        Car::factory()->count(4)->sedan()->active()->sequence(...$this->captainSequence(4, $captainIds, $captainOffset))->create();
        Car::factory()->count(2)->microbus()->active()->sequence(...$this->captainSequence(2, $captainIds, $captainOffset))->create();
        Car::factory()->microbus()->inactive()->create(['captain_id' => null]);
        Car::factory()->microbus()->maintenance()->create(['captain_id' => null]);
        Car::factory()->sedan()->inUse()->sequence(...$this->captainSequence(1, $captainIds, $captainOffset))->create();
    }

    /**
     * @return array<int, array{captain_id: int|null}>
     */
    private function captainSequence(int $count, Collection $captainIds, int &$offset): array
    {
        if ($captainIds->isEmpty()) {
            return array_fill(0, $count, ['captain_id' => null]);
        }

        $sequence = [];
        for ($i = 0; $i < $count; $i++) {
            $sequence[] = ['captain_id' => (int) $captainIds[$offset % $captainIds->count()]];
            $offset++;
        }

        return $sequence;
    }
}
