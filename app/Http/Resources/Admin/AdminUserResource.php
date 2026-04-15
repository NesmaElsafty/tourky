<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Admin\RoleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'type' => $this->type,
            'email' => $this->email,
            'language' => $locale,
            'avatar' => $this->getMedia('avatar')->first()?->getUrl(),
            'role' => new RoleResource($this->role),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function resolveLocale(Request $request): string
    {
        $userLanguage = strtolower((string) ($this->language ?? ''));
        if ($userLanguage === 'en' || $userLanguage === 'ar') {
            return $userLanguage;
        }

        $headerLanguage = strtolower((string) $request->header('lang', ''));
        return $headerLanguage === 'ar' ? 'ar' : 'en';
    }
}
