<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $pointsCount = $this->relationLoaded('points')
            ? $this->points->count()
            : (int) ($this->points_count ?? 0);

        return [
            'id' => $this->id,
            'name' => $this->localized('name_en', 'name_ar', $locale),
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'start_point' => $this->localized('start_point_en', 'start_point_ar', $locale),
            'start_point_en' => $this->start_point_en,
            'start_point_ar' => $this->start_point_ar,

            'end_point' => $this->localized('end_point_en', 'end_point_ar', $locale),
            'end_point_en' => $this->end_point_en,
            'end_point_ar' => $this->end_point_ar,

            'start_lat' => $this->start_lat,
            'start_long' => $this->start_long,
            'end_lat' => $this->end_lat,
            'end_long' => $this->end_long,

            'is_active' => (bool) $this->is_active,

            'points_count' => $pointsCount,
            'points' => PointResource::collection($this->whenLoaded('points')),
            'language' => $locale,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function localized(string $enKey, string $arKey, string $locale): ?string
    {
        $en = $this->{$enKey};
        $ar = $this->{$arKey};

        if ($locale === 'ar') {
            return $ar ?? $en;
        }

        return $en ?? $ar;
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
