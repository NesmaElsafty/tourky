<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed roles in parent-first order. `parent` is the parent role's English name, or null for root.
     *
     * @return list<array{
     *     name_en: string,
     *     name_ar: string,
     *     description_en: string|null,
     *     description_ar: string|null,
     *     parent: string|null
     * }>
     */
    private function definitions(): array
    {
        return [
            [
                'name_en' => 'Super Admin',
                'name_ar' => 'مدير النظام',
                'description_en' => 'Full access to every permission, including roles and permission management.',
                'description_ar' => 'صلاحية كاملة لكل الأذونات، بما فيها إدارة الأدوار والأذونات.',
                'parent' => null,
            ],
            [
                'name_en' => 'Admin',
                'name_ar' => 'مدير',
                'description_en' => 'Day-to-day administration without changing roles or global permissions.',
                'description_ar' => 'إدارة يومية دون تعديل الأدوار أو تعريفات الأذونات العامة.',
                'parent' => 'Super Admin',
            ],
            [
                'name_en' => 'Support',
                'name_ar' => 'دعم فني',
                'description_en' => 'Read-only access to dashboards and records for support staff.',
                'description_ar' => 'وصول للقراءة فقط للوحات والسجلات لفريق الدعم.',
                'parent' => 'Admin',
            ],
            [
                'name_en' => 'Junior Support',
                'name_ar' => 'دعم مبتدئ',
                'description_en' => 'Limited read access for trainees (dashboard, clients, captains).',
                'description_ar' => 'وصول قراءة محدود للمتدربين (لوحة التحكم، العملاء، الكباتن).',
                'parent' => 'Support',
            ],
            [
                'name_en' => 'Operations Manager',
                'name_ar' => 'مدير العمليات',
                'description_en' => 'Manages captains, clients, and bookings without system role configuration.',
                'description_ar' => 'إدارة الكباتن والعملاء والحجوزات دون ضبط أدوار النظام.',
                'parent' => 'Admin',
            ],
            [
                'name_en' => 'Content Editor',
                'name_ar' => 'محرر المحتوى',
                'description_en' => 'Manages media assets and views reports.',
                'description_ar' => 'إدارة الوسائط وعرض التقارير.',
                'parent' => 'Admin',
            ],
            [
                'name_en' => 'Finance Analyst',
                'name_ar' => 'محلل مالي',
                'description_en' => 'Views financial-related modules: reports, bookings, clients, and settings.',
                'description_ar' => 'عرض الوحدات المالية: التقارير، الحجوزات، العملاء، والإعدادات.',
                'parent' => 'Admin',
            ],
            [
                'name_en' => 'Regional Coordinator',
                'name_ar' => 'منسق إقليمي',
                'description_en' => 'Coordinates captains and bookings in the field with limited client access.',
                'description_ar' => 'تنسيق الكباتن والحجوزات في الميدان مع وصول محدود للعملاء.',
                'parent' => 'Admin',
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->definitions() as $row) {
            $parentId = null;
            if ($row['parent'] !== null) {
                $parentId = Role::query()->where('name_en', $row['parent'])->value('id');
            }

            Role::updateOrCreate(
                ['name_en' => $row['name_en']],
                [
                    'name_ar' => $row['name_ar'],
                    'description_en' => $row['description_en'],
                    'description_ar' => $row['description_ar'],
                    'role_id' => $parentId,
                ]
            );
        }
    }
}
