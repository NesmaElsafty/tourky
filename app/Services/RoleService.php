<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleService
{
    public function getAllRoles()
    {
        $roles = Role::all();

        return $roles;
    }

    public function createRole(Request $request)
    {
        $role = Role::create([
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'description_en' => $request->description_en,
            'description_ar' => $request->description_ar,
        ]);

        return $role;
    }

    public function updateRole(Request $request, $id)
    {
        $role = Role::find($id);
        $role->update([
            'name_en' => $request->name_en ?? $role->name_en,
            'name_ar' => $request->name_ar ?? $role->name_ar,
            'description_en' => $request->description_en ?? $role->description_en,
            'description_ar' => $request->description_ar ?? $role->description_ar,
        ]);

        return $role;
    }

    public function deleteRole($id)
    {
        $role = Role::find($id);
        $role->delete();

        return $role;
    }

    public function getRoleById($id)
    {
        $role = Role::find($id);

        return $role;
    }
}
