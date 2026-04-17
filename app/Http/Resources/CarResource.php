<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $type = $this->type;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'number_of_seats' => $this->number_of_seats,
            'type' => $type,
            'type_label' => $type !== null ? __('api.cars.type_labels.'.$type) : null,
            'plate_numbers' => $this->plate_numbers,
            'plate_letters' => $this->plate_letters,
            'color' => $this->color,
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
