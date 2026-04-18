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
        $notification = $this->notification;

        return array_merge(
            [
                'delivery_id' => $this->id,
                'fired_at' => $this->created_at,
            ],
            $notification !== null
                ? (new NotificationResource($notification))->toArray($request)
                : [
                    'id' => null,
                    'title' => null,
                    'title_en' => null,
                    'title_ar' => null,
                    'description' => null,
                    'description_en' => null,
                    'description_ar' => null,
                    'user_type' => null,
                    'language' => $this->resolveLocale($request),
                    'created_at' => null,
                    'updated_at' => null,
                ],
        );
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
