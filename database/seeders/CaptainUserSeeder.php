<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CaptainUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['phone' => '01000000002', 'type' => 'captain'],
            [
                'name' => 'Captain User',
                'email' => 'captain@tourky.local',
                'password' => Hash::make('123456'),
                'language' => 'en',
                'status' => 'available',
                'has_trip' => false,
                'trip_id' => null,
                'lat' => 30.0444,
                'long' => 31.2357,
            ]
        );

        User::factory()
            ->captain()
            ->count(20)
            ->create();
    }
}
