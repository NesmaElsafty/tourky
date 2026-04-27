<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request)
    {
        // in case of index, return the name, dec depnended on user language
        $user = $request->user();
        $case = $request->route()->getName() ?? 'index';
        $name = $this->name_en;
        $description = $this->description_en;

        if ($user?->language == 'ar') {
            $name = $this->name_ar;
            $description = $this->description_ar;
        }
        
            $data = [
                    'id' => $this->id,
                    'name_en' => $this->name_en,
                    'name_ar' => $this->name_ar,
                    'description_en' => $this->description_en,
                    'description_ar' => $this->description_ar,
                    'name' => $name,
                    'description' => $description,
                    'parent_id' => $this->role_id,
            ];
            $data['permissions'] = $this->permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name_en' => $permission->display_name_en,
                    'display_name_ar' => $permission->display_name_ar,
                    'description_en' => $permission->description_en,
                    'description_ar' => $permission->description_ar,
                    'group_en' => $permission->group_en,
                    'group_ar' => $permission->group_ar,
                ];
            });
        
        return $data;
    }
}
