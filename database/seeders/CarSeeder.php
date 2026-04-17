<?php

namespace Database\Seeders;

use App\Models\Car;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        Car::factory()->count(8)->create();
        Car::factory()->count(4)->sedan()->create();
        Car::factory()->count(3)->microbus()->create();
    }
}
