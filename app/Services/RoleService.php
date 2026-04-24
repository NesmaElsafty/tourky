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

    public function createRole($data)
    {
        $role = Role::create([
            'name_en' => $data['name_en'],
            'name_ar' => $data['name_ar'],
            'description_en' => $data['description_en'],
            'description_ar' => $data['description_ar'],
            'role_id' => $data['parent_id'],
        ]);

        return $role;
    }

    public function updateRole($data, $id)
    {
        $role = Role::find($id);
        $role->name_en = $data['name_en'] ?? $role->name_en;
        $role->name_ar = $data['name_ar'] ?? $role->name_ar;
        $role->description_en = $data['description_en'] ?? $role->description_en;
        $role->description_ar = $data['description_ar'] ?? $role->description_ar;
        $role->role_id = $data['parent_id'] ?? $role->role_id;
        $role->save();

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
