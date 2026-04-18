<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'type' => $this->type,
            'type_label' => $this->type !== null ? __('api.users.type_labels.'.$this->type) : null,
            'language' => $this->language,
            'is_blocked' => $this->trashed(),
            'blocked_at' => $this->deleted_at,
            'role' => $this->when(
                $this->relationLoaded('role') && $this->role !== null && $this->type === 'admin',
                fn () => [
                    'id' => $this->role->id,
                    'name' => $locale === 'ar'
                        ? ($this->role->name_ar ?? $this->role->name_en)
                        : ($this->role->name_en ?? $this->role->name_ar),
                    'name_en' => $this->role->name_en,
                    'name_ar' => $this->role->name_ar,
                ],
            ),
            'role_id' => $this->when($this->type === 'admin', $this->role_id),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'display_locale' => $locale,
        ];
    }

    private function resolveLocale(Request $request): string
    {
        $user = $request->user();
        if ($user !== null) {
            $language = strtolower((string) $user->getAttribute('language'));
            if ($language === 'en' || $language === 'ar') {
                return $language;
            }
        }

        $headerLanguage = strtolower((string) $request->header('lang', ''));

        return $headerLanguage === 'ar' ? 'ar' : 'en';
    }
}
