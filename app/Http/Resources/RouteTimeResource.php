<?php

namespace App\Http\Resources;

use App\Models\Time;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteTimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $timeIds = collect($this->time_ids ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        $times = Time::query()
            ->with('point:id,route_id')
            ->whereIn('id', $timeIds->all())
            ->orderBy('pickup_time')
            ->get()
            ->map(static fn (Time $time): array => [
                'id' => $time->id,
                'pickup_time' => $time->pickup_time,
                'point_id' => $time->point_id,
            ])
            ->values();

        return [
            'id' => $this->id,
            'route_id' => $this->route_id,
            'time_ids' => $timeIds,
            'times' => $times,
            'route' => $this->when(
                $this->relationLoaded('route') && $this->route !== null,
                fn () => [
                    'id' => $this->route->id,
                    'name' => $locale === 'ar'
                        ? ($this->route->name_ar ?? $this->route->name_en)
                        : ($this->route->name_en ?? $this->route->name_ar),
                    'name_en' => $this->route->name_en,
                    'name_ar' => $this->route->name_ar,
                    'is_active' => (bool) $this->route->is_active,
                ]
            ),
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
