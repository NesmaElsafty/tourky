<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * @return list<array{
     *     name: string,
     *     display_name_en: string,
     *     display_name_ar: string,
     *     description_en: string|null,
     *     description_ar: string|null,
     *     group_en: string,
     *     group_ar: string
     * }>
     */
    private function definitions(): array
    {
        return [
            [
                'name' => 'dashboard.view',
                'display_name_en' => 'View dashboard',
                'display_name_ar' => 'عرض لوحة التحكم',
                'description_en' => 'Access the admin dashboard.',
                'description_ar' => 'الوصول إلى لوحة تحكم الإدارة.',
                'group_en' => 'general',
                'group_ar' => 'عام',
            ],
            [
                'name' => 'admin-users.view',
                'display_name_en' => 'View admin users',
                'display_name_ar' => 'عرض مستخدمي الإدارة',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'admin_users',
                'group_ar' => 'مستخدمي الإدارة',
            ],
            [
                'name' => 'admin-users.create',
                'display_name_en' => 'Create admin users',
                'display_name_ar' => 'إنشاء مستخدمي إدارة',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'admin_users',
                'group_ar' => 'مستخدمي الإدارة',
            ],
            [
                'name' => 'admin-users.update',
                'display_name_en' => 'Update admin users',
                'display_name_ar' => 'تحديث مستخدمي الإدارة',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'admin_users',
                'group_ar' => 'مستخدمي الإدارة',
            ],
            [
                'name' => 'admin-users.delete',
                'display_name_en' => 'Delete admin users',
                'display_name_ar' => 'حذف مستخدمي الإدارة',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'admin_users',
                'group_ar' => 'مستخدمي الإدارة',
            ],
            [
                'name' => 'roles.view',
                'display_name_en' => 'View roles',
                'display_name_ar' => 'عرض الأدوار',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'roles',
                'group_ar' => 'الأدوار',
            ],
            [
                'name' => 'roles.manage',
                'display_name_en' => 'Manage roles',
                'display_name_ar' => 'إدارة الأدوار',
                'description_en' => 'Create, update, or delete roles and assign permissions.',
                'description_ar' => 'إنشاء أو تعديل أو حذف الأدوار وربط الأذونات.',
                'group_en' => 'roles',
                'group_ar' => 'الأدوار',
            ],
            [
                'name' => 'permissions.view',
                'display_name_en' => 'View permissions',
                'display_name_ar' => 'عرض الأذونات',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'permissions',
                'group_ar' => 'الأذونات',
            ],
            [
                'name' => 'permissions.manage',
                'display_name_en' => 'Manage permissions',
                'display_name_ar' => 'إدارة الأذونات',
                'description_en' => 'Create or update permission definitions.',
                'description_ar' => 'إنشاء أو تعديل تعريفات الأذونات.',
                'group_en' => 'permissions',
                'group_ar' => 'الأذونات',
            ],
            [
                'name' => 'captains.view',
                'display_name_en' => 'View captains',
                'display_name_ar' => 'عرض الكباتن',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'captains',
                'group_ar' => 'الكباتن',
            ],
            [
                'name' => 'captains.manage',
                'display_name_en' => 'Manage captains',
                'display_name_ar' => 'إدارة الكباتن',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'captains',
                'group_ar' => 'الكباتن',
            ],
            [
                'name' => 'clients.view',
                'display_name_en' => 'View clients',
                'display_name_ar' => 'عرض العملاء',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'clients',
                'group_ar' => 'العملاء',
            ],
            [
                'name' => 'clients.manage',
                'display_name_en' => 'Manage clients',
                'display_name_ar' => 'إدارة العملاء',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'clients',
                'group_ar' => 'العملاء',
            ],
            [
                'name' => 'bookings.view',
                'display_name_en' => 'View bookings',
                'display_name_ar' => 'عرض الحجوزات',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'bookings',
                'group_ar' => 'الحجوزات',
            ],
            [
                'name' => 'bookings.manage',
                'display_name_en' => 'Manage bookings',
                'display_name_ar' => 'إدارة الحجوزات',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'bookings',
                'group_ar' => 'الحجوزات',
            ],
            [
                'name' => 'settings.view',
                'display_name_en' => 'View settings',
                'display_name_ar' => 'عرض الإعدادات',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'settings',
                'group_ar' => 'الإعدادات',
            ],
            [
                'name' => 'settings.manage',
                'display_name_en' => 'Manage settings',
                'display_name_ar' => 'إدارة الإعدادات',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'settings',
                'group_ar' => 'الإعدادات',
            ],
            [
                'name' => 'media.view',
                'display_name_en' => 'View media',
                'display_name_ar' => 'عرض الوسائط',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'media',
                'group_ar' => 'الوسائط',
            ],
            [
                'name' => 'media.manage',
                'display_name_en' => 'Manage media',
                'display_name_ar' => 'إدارة الوسائط',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'media',
                'group_ar' => 'الوسائط',
            ],
            [
                'name' => 'reports.view',
                'display_name_en' => 'View reports',
                'display_name_ar' => 'عرض التقارير',
                'description_en' => null,
                'description_ar' => null,
                'group_en' => 'reports',
                'group_ar' => 'التقارير',
            ],
            [
                'name' => 'terms.view',
                'display_name_en' => 'View terms',
                'display_name_ar' => 'عرض الشروط والأحكام',
                'description_en' => 'View terms, policies, and FAQs.',
                'description_ar' => 'عرض الشروط والسياسات والأسئلة الشائعة.',
                'group_en' => 'terms',
                'group_ar' => 'الشروط والأحكام',
            ],
            [
                'name' => 'terms.manage',
                'display_name_en' => 'Manage terms',
                'display_name_ar' => 'إدارة الشروط والأحكام',
                'description_en' => 'Create, update, or delete terms and related content.',
                'description_ar' => 'إنشاء أو تعديل أو حذف الشروط والمحتوى المرتبط.',
                'group_en' => 'terms',
                'group_ar' => 'الشروط والأحكام',
            ],
            [
                'name' => 'notifications.view',
                'display_name_en' => 'View notifications',
                'display_name_ar' => 'عرض الإشعارات',
                'description_en' => 'View in-app notification templates and messages.',
                'description_ar' => 'عرض قوالب الإشعارات والرسائل داخل التطبيق.',
                'group_en' => 'notifications',
                'group_ar' => 'الإشعارات',
            ],
            [
                'name' => 'notifications.manage',
                'display_name_en' => 'Manage notifications',
                'display_name_ar' => 'إدارة الإشعارات',
                'description_en' => 'Create, update, or delete notification content.',
                'description_ar' => 'إنشاء أو تعديل أو حذف محتوى الإشعارات.',
                'group_en' => 'notifications',
                'group_ar' => 'الإشعارات',
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->definitions() as $row) {
            Permission::updateOrCreate(
                ['name' => $row['name']],
                [
                    'display_name_en' => $row['display_name_en'],
                    'display_name_ar' => $row['display_name_ar'],
                    'description_en' => $row['description_en'],
                    'description_ar' => $row['description_ar'],
                    'group_en' => $row['group_en'],
                    'group_ar' => $row['group_ar'],
                ]
            );
        }
    }
}
