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

        $viewOnly = Permission::query()
            ->where('name', 'like', '%.view')
            ->orderBy('id')
            ->pluck('id');

        $juniorSupportNames = [
            'dashboard.view',
            'routes.view',
            'clients.view',
            'captains.view',
            'terms.view',
            'notifications.view',
        ];

        $contentEditorNames = [
            'dashboard.view',
            'routes.view',
            'media.view',
            'media.manage',
            'reports.view',
            'terms.view',
            'terms.manage',
            'notifications.view',
            'notifications.manage',
        ];

        $financeAnalystNames = [
            'dashboard.view',
            'routes.view',
            'reports.view',
            'bookings.view',
            'clients.view',
            'captains.view',
            'settings.view',
            'admin-users.view',
            'media.view',
        ];

        $regionalCoordinatorNames = [
            'dashboard.view',
            'routes.view',
            'captains.view',
            'captains.manage',
            'clients.view',
            'bookings.view',
            'bookings.manage',
            'media.view',
            'notifications.view',
            'notifications.manage',
        ];

        $map = [
            'Super Admin' => $allPermissionIds,
            'Admin' => $adminLikeExclude,
            'Support' => $viewOnly,
            'Junior Support' => Permission::query()
                ->whereIn('name', $juniorSupportNames)
                ->orderBy('id')
                ->pluck('id'),
            'Operations Manager' => $adminLikeExclude,
            'Content Editor' => Permission::query()
                ->whereIn('name', $contentEditorNames)
                ->orderBy('id')
                ->pluck('id'),
            'Finance Analyst' => Permission::query()
                ->whereIn('name', $financeAnalystNames)
                ->orderBy('id')
                ->pluck('id'),
            'Regional Coordinator' => Permission::query()
                ->whereIn('name', $regionalCoordinatorNames)
                ->orderBy('id')
                ->pluck('id'),
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
