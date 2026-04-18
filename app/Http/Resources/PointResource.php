<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $timesCount = $this->relationLoaded('times')
            ? $this->times->count()
            : (int) ($this->times_count ?? 0);

        return [
            'id' => $this->id,
            'name' => $this->localized('name_en', 'name_ar', $locale),
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'lat' => $this->lat,
            'long' => $this->long,
            'route_id' => $this->route_id,
            'times_count' => $timesCount,
            'times' => TimeResource::collection(times),
            // 'route' => $this->when(
            //     $this->relationLoaded('route'),
            //     fn () => [
            //         'id' => $this->route->id,
            //         'name' => $locale === 'ar'
            //             ? ($this->route->name_ar ?? $this->route->name_en)
            //             : ($this->route->name_en ?? $this->route->name_ar),
            //         'name_en' => $this->route->name_en,
            //         'name_ar' => $this->route->name_ar,
            //     ]
            // ),
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
