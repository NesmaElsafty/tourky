<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::query()->where('name_en', 'Super Admin')->firstOrFail();
        $staffAdminRole = Role::query()->where('name_en', 'Admin')->firstOrFail();
        $companyRole = Role::query()->where('name_en', 'Company')->firstOrFail();

        User::query()->updateOrCreate(
            ['phone' => '01000000001'],
            [
                'name' => 'Admin User',
                'email' => 'admin@tourky.local',
                'password' => Hash::make('123456'),
                'language' => 'en',
                'type' => 'admin',
                'role_id' => $superAdminRole->id,
                'company_id' => null,
            ]
        );

        User::query()->updateOrCreate(
            ['phone' => '01000000002'],
            [
                'name' => 'cib',
                'email' => 'cib@tourky.local',
                'password' => Hash::make('123456'),
                'language' => 'en',
                'type' => 'admin',
                'role_id' => $companyRole->id,
                'company_id' => null,
            ]
        );

        User::factory()
            ->count(2)
            ->create([
                'type' => 'admin',
                'role_id' => $companyRole->id,
                'company_id' => null,
            ]);

        User::factory()
            ->count(20)
            ->create([
                'type' => 'admin',
                'role_id' => $staffAdminRole->id,
                'company_id' => null,
            ]);
    }
}
