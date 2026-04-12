<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Client User',
            'phone' => '01000000003',
            'email' => 'client@tourky.local',
            'password' => Hash::make('123456'),
            'language' => 'ar',
            'type' => 'client',
        ]);

        User::factory()
            ->count(20)
            ->create([
                'type' => 'client',
            ]);
    }
}
