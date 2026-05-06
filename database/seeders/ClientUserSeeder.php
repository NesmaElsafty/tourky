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
        User::query()->updateOrCreate(
            ['phone' => '01000000003', 'type' => 'client'],
            [
                'name' => 'Client User',
                'email' => 'client@tourky.local',
                'password' => Hash::make('123456'),
                'language' => 'ar',
                'company_id' => null,
            ]
        );
    }
}
