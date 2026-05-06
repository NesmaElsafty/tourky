<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissionIds = Permission::query()->orderBy('id')->pluck('id');

        $adminLikeExclude = Permission::query()
            ->whereNotIn('name', ['roles.manage', 'permissions.manage'])
            ->orderBy('id')
            ->pluck('id');

        $map = [
            'Admin' => $allPermissionIds,
            'Employee' => $adminLikeExclude,
            'Company' => Permission::query()
                ->whereIn('name', [
                    'dashboard.view',
                    'routes.view',
                    'clients.view',
                    'clients.manage',
                ])
                ->orderBy('id')
                ->pluck('id'),
        ];

        foreach ($map as $roleName => $permissionIds) {
            $role = Role::query()->where('name_en', $roleName)->firstOrFail();
            $role->permissions()->sync($permissionIds);
        }
    }
}
