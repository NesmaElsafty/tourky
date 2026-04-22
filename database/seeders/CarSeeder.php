<?php

namespace Database\Seeders;

use App\Models\Car;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        Car::factory()->count(8)->active()->create();
        Car::factory()->count(4)->sedan()->active()->create();
        Car::factory()->count(2)->microbus()->active()->create();
        Car::factory()->microbus()->inactive()->create();
        Car::factory()->microbus()->maintenance()->create();
        Car::factory()->sedan()->inUse()->create();
    }
}
