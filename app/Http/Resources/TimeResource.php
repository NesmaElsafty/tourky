<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        return [
            'id' => $this->id,
            'pickup_time' => $this->pickup_time,
            'is_active' => (bool) $this->is_active,
            'point_id' => $this->point_id,
            'point' => $this->when(
                $this->relationLoaded('point'),
                fn () => [
                    'id' => $this->point->id,
                    'name' => $locale === 'ar'
                        ? ($this->point->name_ar ?? $this->point->name_en)
                        : ($this->point->name_en ?? $this->point->name_ar),
                    'name_en' => $this->point->name_en,
                    'name_ar' => $this->point->name_ar,
                ]
            ),
            'language' => $locale,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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
