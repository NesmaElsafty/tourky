<?php

namespace App\Http\Resources\Concerns;

use App\Support\ApiLocale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait ResolvesApiLocale
{
    private function resolveLocale(Request $request): string
    {
        $user = $request->user();
        if ($user !== null) {
            $language = strtolower((string) $user->getAttribute('language'));
            if ($language === 'en' || $language === 'ar') {
                return $language;
            }
        }

        return ApiLocale::fromRequest($request);
    }

    /**
     * @param  Model  $model
     */
    private function localizedModel($model, string $enKey, string $arKey, string $locale): ?string
    {
        $en = $model->{$enKey};
        $ar = $model->{$arKey};

        if ($locale === 'ar') {
            return $ar ?? $en;
        }

        return $en ?? $ar;
    }
}
