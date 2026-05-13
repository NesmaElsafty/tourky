<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientUserSeeder extends Seeder
{
    /**
     * Extra B2C clients so reservation/trip seeders can build large pending groups (TripSeeder needs 16+).
     */
    private const EXTRA_CLIENT_COUNT = 32;

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

        for ($i = 1; $i <= self::EXTRA_CLIENT_COUNT; $i++) {
            $suffix = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $phone = '01000002'.$suffix;

            User::query()->updateOrCreate(
                ['phone' => $phone, 'type' => 'client'],
                [
                    'name' => 'Demo Client '.$i,
                    'email' => 'demo.client.'.$i.'@tourky.local',
                    'password' => Hash::make('123456'),
                    'language' => $i % 2 === 0 ? 'en' : 'ar',
                    'company_id' => null,
                ]
            );
        }
    }
}
