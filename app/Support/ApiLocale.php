<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class ApiLocale
{
    /**
     * Resolve locale for guests from the lang header only.
     */
    public static function fromRequest(Request $request): string
    {
        $lang = strtolower((string) $request->header('lang', ''));
        if ($lang === 'en' || $lang === 'ar') {
            return $lang;
        }

        $default = strtolower((string) config('app.locale', 'en'));
        return $default === 'ar' ? 'ar' : 'en';
    }

    public static function apply(string $locale): void
    {
        app()->setLocale(strtolower($locale) === 'ar' ? 'ar' : 'en');
    }

    /** Use authenticated user's language only (en|ar). */
    public static function applyFromUserLanguage(?Authenticatable $user): void
    {
        if ($user === null) {
            return;
        }

        $language = strtolower((string) $user->getAttribute('language'));
        if ($language === 'en' || $language === 'ar') {
            app()->setLocale($language);
        }
    }
}
