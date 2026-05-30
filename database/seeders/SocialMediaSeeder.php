<?php

namespace Database\Seeders;

use App\Models\SocialMedia;
use Illuminate\Database\Seeder;

class SocialMediaSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            ['name' => 'facebook', 'url' => 'https://facebook.com/tourkygroup'],
            ['name' => 'instagram', 'url' => 'https://instagram.com/tourkygroup'],
            ['name' => 'twitter', 'url' => 'https://twitter.com/tourkygroup'],
            ['name' => 'youtube', 'url' => 'https://youtube.com/@tourkygroup'],
            ['name' => 'linkedin', 'url' => 'https://linkedin.com/company/tourkygroup'],
        ];

        foreach ($links as $link) {
            SocialMedia::query()->updateOrCreate(
                ['name' => $link['name']],
                [
                    'url' => $link['url'],
                    'is_active' => true,
                ]
            );
        }
    }
}
