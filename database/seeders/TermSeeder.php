<?php

namespace Database\Seeders;

use App\Models\Term;
use Illuminate\Database\Seeder;

class TermSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['client', 'captain'] as $userType) {
            foreach (Term::TYPES as $type) {
                Term::query()->create([
                    'name_en' => $this->titleEn($type, $userType),
                    'name_ar' => $this->titleAr($type, $userType),
                    'description_en' => $this->bodyEn($type, $userType),
                    'description_ar' => $this->bodyAr($type, $userType),
                    'is_active' => true,
                    'type' => $type,
                    'user_type' => $userType,
                ]);
            }
        }
    }

    private function titleEn(string $type, string $userType): string
    {
        return match ($type) {
            'terms_conditions' => 'Terms & conditions ('.$userType.')',
            'privacy_policy' => 'Privacy policy ('.$userType.')',
            default => 'Frequently asked questions ('.$userType.')',
        };
    }

    private function titleAr(string $type, string $userType): string
    {
        return match ($type) {
            'terms_conditions' => 'الشروط والأحكام ('.$userType.')',
            'privacy_policy' => 'سياسة الخصوصية ('.$userType.')',
            default => 'الأسئلة الشائعة ('.$userType.')',
        };
    }

    private function bodyEn(string $type, string $userType): string
    {
        return 'Placeholder content for '.$type.' targeting '.$userType.'. Replace with your legal copy.';
    }

    private function bodyAr(string $type, string $userType): string
    {
        return 'محتوى تجريبي لـ '.$type.' للمستخدم '.$userType.'.';
    }
}
