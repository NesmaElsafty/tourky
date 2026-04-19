<?php

namespace App\Http\Resources;

use App\Models\CaptainReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Client-facing report row (list + detail).
 *
 * @mixin CaptainReport
 */
class ClientReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $reservation = $this->reservation;
        $route = $reservation?->route;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type === CaptainReport::TYPE_TRIP
                ? __('api.reports.type_trip')
                : __('api.reports.type_captain'),
            'message' => $this->message,
            'trip_id' => $this->trip_id,
            'reservation_id' => $this->reservation_id,
            'trip_date' => $this->whenLoaded('trip', fn () => $this->trip?->date ?? $reservation?->date),
            'pickup_time' => $this->when(
                $this->relationLoaded('trip')
                    && $this->trip !== null
                    && $this->trip->relationLoaded('time')
                    && $this->trip->time !== null,
                fn () => $this->trip->time->pickup_time
            ),
            'route' => $this->when(
                $reservation !== null && $route !== null,
                fn () => [
                    'id' => $route->id,
                    'name' => $locale === 'ar' ? ($route->name_ar ?? $route->name_en) : ($route->name_en ?? $route->name_ar),
                ]
            ),
            'captain' => $this->when(
                $this->type === CaptainReport::TYPE_CAPTAIN
                    && $this->relationLoaded('captain')
                    && $this->captain !== null,
                fn () => [
                    'id' => $this->captain->id,
                    'name' => $this->captain->name,
                ]
            ),
            'admin_reply' => $this->admin_reply,
            'replied_at' => $this->replied_at?->toIso8601String(),
            'replied_by' => $this->when(
                $this->relationLoaded('repliedByUser') && $this->repliedByUser !== null,
                fn () => [
                    'id' => $this->repliedByUser->id,
                    'name' => $this->repliedByUser->name,
                ]
            ),
            'created_at' => $this->created_at?->toIso8601String(),
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
