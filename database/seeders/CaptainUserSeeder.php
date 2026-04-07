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
        User::create([
            'name' => 'Captain User',
            'phone' => '01000000002',
            'password' => Hash::make('password'),
            'type' => 'captain',
        ]);

        User::factory()
            ->count(20)
            ->create([
                'type' => 'captain',
            ]);
    }
}
