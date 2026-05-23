<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CaptainUserSeeder extends Seeder
{
    /**
     * Extra captains so multi-vehicle trips and trip seeding never run out of distinct drivers.
     */
    private const EXTRA_CAPTAIN_COUNT = 10;

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
                'license_expiry_date' => now()->addYears(2)->toDateString(),
            ]
        );

        for ($i = 1; $i <= self::EXTRA_CAPTAIN_COUNT; $i++) {
            $suffix = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $phone = '01000003'.$suffix;

            User::query()->updateOrCreate(
                ['phone' => $phone, 'type' => 'captain'],
                [
                    'name' => 'Demo Captain '.$i,
                    'email' => 'demo.captain.'.$i.'@tourky.local',
                    'password' => Hash::make('123456'),
                    'language' => 'en',
                    'status' => 'available',
                    'has_trip' => false,
                    'trip_id' => null,
                    'lat' => 30.0444,
                    'long' => 31.2357,
                    'license_expiry_date' => now()->addYears(2)->addMonths($i)->toDateString(),
                ]
            );
        }
    }
}
