<?php

namespace App\Http\Resources\Concerns;

use App\Support\ApiLocale;
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
}
