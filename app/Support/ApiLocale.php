<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class ApiLocale
{
    public const SUPPORTED = ['en', 'ar'];

    public static function normalize(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $v = strtolower(trim($value));
        if (str_starts_with($v, 'ar')) {
            return 'ar';
        }
        if (str_starts_with($v, 'en')) {
            return 'en';
        }

        return null;
    }

    /**
     * Resolve locale from the lang header (and simple Accept-Language prefix as fallback).
     */
    public static function fromRequest(Request $request): string
    {
        $fromLangHeader = self::normalize($request->header('lang'));
        if ($fromLangHeader !== null) {
            return $fromLangHeader;
        }

        $accept = $request->header('Accept-Language');
        if ($accept !== null && $accept !== '') {
            $first = trim(explode(',', $accept)[0]);
            $first = strtolower(trim(explode(';', $first)[0]));
            $fromAccept = self::normalize($first);
            if ($fromAccept !== null) {
                return $fromAccept;
            }
        }

        $default = config('app.locale', 'en');

        return self::clamp($default);
    }

    public static function clamp(string $locale): string
    {
        return in_array($locale, self::SUPPORTED, true) ? $locale : 'en';
    }

    public static function apply(string $locale): void
    {
        app()->setLocale(self::clamp($locale));
    }

    /**
     * Use the authenticated user's stored language for translated response messages (en|ar, any common casing).
     */
    public static function applyFromUserLanguage(?Authenticatable $user): void
    {
        if ($user === null) {
            return;
        }

        $raw = $user->getAttribute('language');
        if ($raw === null || $raw === '') {
            return;
        }

        $locale = self::normalize((string) $raw);
        if ($locale !== null) {
            self::apply($locale);
        }
    }
}
