<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'phone' => '01000000001',
            'password' => Hash::make('password'),
            'type' => 'admin',
        ]);

        User::factory()
            ->count(20)
            ->create([
                'type' => 'admin',
            ]);
    }
}
