<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        return [
            'id' => $this->id,
            'title' => $this->localized('title_en', 'title_ar', $locale),
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'description' => $this->localized('description_en', 'description_ar', $locale),
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'user_type' => $this->user_type,
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
