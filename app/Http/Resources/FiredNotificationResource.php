<?php

namespace App\Http\Resources;

use App\Models\NotificationDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin NotificationDelivery
 */
class FiredNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return array_merge(
            [
                'delivery_id' => $this->id,
                'notification_id' => $this->notification_id,
                'fired_at' => $this->created_at,
                'read_at' => $this->read_at,
                'is_read' => $this->read_at !== null,
            ],
            $this->notification !== null
                ? (new NotificationResource($this->notification))->toArray($request)
                : $this->directDeliveryPayload($locale),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function directDeliveryPayload(string $locale): array
    {
        return [
            'id' => null,
            'title' => $this->localizedTitle($locale),
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'description' => $this->localizedDescription($locale),
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'user_type' => $this->user?->type,
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
