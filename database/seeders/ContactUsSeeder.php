<?php

namespace Database\Seeders;

use App\Models\ContactUs;
use Illuminate\Database\Seeder;

class ContactUsSeeder extends Seeder
{
    public function run(): void
    {
        ContactUs::query()->updateOrCreate(
            ['email' => 'info@tourkygroup.com'],
            [
                'phone' => '01000000000',
            ]
        );
    }
}
