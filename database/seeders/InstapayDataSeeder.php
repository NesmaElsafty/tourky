<?php

namespace Database\Seeders;

use App\Models\InstapayData;
use Illuminate\Database\Seeder;

class InstapayDataSeeder extends Seeder
{
    public function run(): void
    {
        InstapayData::query()->updateOrCreate(
            ['number' => '01000000000', 'type' => 'wallet'],
            [
                'is_active' => '1',
            ]
        );

        InstapayData::query()->updateOrCreate(
            ['number' => '1234567890123456', 'type' => 'bank_account'],
            [
                'is_active' => '1',
            ]
        );
    }
}
