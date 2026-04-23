<?php

namespace Database\Seeders;

use App\Models\Role;
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
            'company_id' => null,
        ]);

        $companyRoleId = Role::query()->where('name_en', 'Company')->value('id');

        if ($companyRoleId !== null) {
            $companyAdmins = User::query()
                ->where('type', 'admin')
                ->where('role_id', $companyRoleId)
                ->get();

            foreach ($companyAdmins as $companyAdmin) {
                User::factory()
                    ->count(4)
                    ->create([
                        'type' => 'client',
                        'company_id' => $companyAdmin->id,
                        'role_id' => null,
                    ]);
            }
        }

        User::factory()
            ->count(20)
            ->create([
                'type' => 'client',
                'company_id' => null,
            ]);
    }
}
