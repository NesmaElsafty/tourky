<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $permission = [
            'id' => $this->id,
            'name' => $this->name,
            'display_name_en' => $this->display_name_en,
            'display_name_ar' => $this->display_name_ar,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'group_en' => $this->group_en,
            'group_ar' => $this->group_ar,
        ];

        return $permission;
    }
}
