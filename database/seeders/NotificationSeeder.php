<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        Notification::query()->create([
            'title_en' => 'Welcome to Tourky',
            'title_ar' => 'مرحباً بك في توركي',
            'description_en' => 'Thank you for using our client app. Stay tuned for route updates and offers.',
            'description_ar' => 'شكراً لاستخدام تطبيق العميل. تابعنا لتحديثات المسارات والعروض.',
            'user_type' => 'client',
        ]);

        Notification::query()->create([
            'title_en' => 'Captain safety reminder',
            'title_ar' => 'تذكير سلامة للكابتن',
            'description_en' => 'Please verify vehicle documents and passenger count before each trip.',
            'description_ar' => 'يرجى التحقق من أوراق المركبة وعدد الركاب قبل كل رحلة.',
            'user_type' => 'captain',
        ]);

        Notification::query()->create([
            'title_en' => 'Holiday schedule',
            'title_ar' => 'جدول العطلات',
            'description_en' => 'Some routes may run on a reduced schedule during public holidays.',
            'description_ar' => 'قد تعمل بعض المسارات بجدول مخفف خلال العطل الرسمية.',
            'user_type' => 'client',
        ]);
    }
}
