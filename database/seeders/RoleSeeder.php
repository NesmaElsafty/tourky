<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
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
                'name_en' => 'Admin',
                'name_ar' => 'مدير',
                'description_en' => 'Full access to every permission, including roles and permission management.',
                'description_ar' => 'صلاحية كاملة لكل الأذونات، بما فيها إدارة الأدوار والأذونات.',
                'parent' => null,
            ],
            [
                'name_en' => 'Company',
                'name_ar' => 'شركة',
                'description_en' => 'Company account with operational access without role or permission management.',
                'description_ar' => 'حساب شركة مع صلاحيات تشغيلية دون إدارة الأدوار أو الأذونات.',
                'parent' => 'Admin',
            ],
            [
                'name_en' => 'Employee',
                'name_ar' => 'موظف',
                'description_en' => 'Day-to-day administration without changing roles or global permission definitions.',
                'description_ar' => 'إدارة يومية دون تعديل الأدوار أو تعريفات الأذونات العامة.',
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

        $this->migrateUsersOffObsoleteRoles();
        $this->deleteObsoleteRoles();
    }

    private function migrateUsersOffObsoleteRoles(): void
    {
        $keep = ['Admin', 'Company', 'Employee'];

        $admin = Role::query()->where('name_en', 'Admin')->first();
        $employee = Role::query()->where('name_en', 'Employee')->first();

        if ($admin === null || $employee === null) {
            return;
        }

        $obsolete = Role::query()->whereNotIn('name_en', $keep)->get();

        foreach ($obsolete as $role) {
            $targetId = $role->name_en === 'Super Admin'
                ? $admin->id
                : $employee->id;

            User::query()->where('role_id', $role->id)->update(['role_id' => $targetId]);
        }
    }

    private function deleteObsoleteRoles(): void
    {
        $keep = ['Admin', 'Company', 'Employee'];

        while (true) {
            $leaf = Role::query()
                ->whereNotIn('name_en', $keep)
                ->whereDoesntHave('children')
                ->first();

            if ($leaf === null) {
                break;
            }

            $leaf->permissions()->detach();
            $leaf->delete();
        }
    }
}
