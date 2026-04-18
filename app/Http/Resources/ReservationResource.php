<?php

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $status = $this->status;

        return [
            'id' => $this->id,
            'status' => $status,
            'status_label' => $status !== null ? __('api.reservations.status_labels.'.$status) : null,
            'date' => $this->date,
            'route' => $this->when(
                $this->relationLoaded('route') && $this->route !== null,
                fn () => [
                    'id' => $this->route->id,
                    'name' => $this->localizedModel($this->route, 'name_en', 'name_ar', $locale),
                    'name_en' => $this->route->name_en,
                    'name_ar' => $this->route->name_ar,
                    'is_active' => (bool) $this->route->is_active,
                ]
            ),
            'point' => $this->when(
                $this->relationLoaded('point') && $this->point !== null,
                fn () => [
                    'id' => $this->point->id,
                    'name' => $this->localizedModel($this->point, 'name_en', 'name_ar', $locale),
                    'name_en' => $this->point->name_en,
                    'name_ar' => $this->point->name_ar,
                    'lat' => $this->point->lat,
                    'long' => $this->point->long,
                ]
            ),
            'time' => $this->when(
                $this->relationLoaded('time') && $this->time !== null,
                new TimeResource($this->time),
            ),
            'user' => $this->when(
                $this->relationLoaded('user') && $this->user !== null,
                fn () => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'phone' => $this->user->phone,
                    'email' => $this->user->email,
                    'type' => $this->user->type,
                ]
            ),
            'language' => $locale,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
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
