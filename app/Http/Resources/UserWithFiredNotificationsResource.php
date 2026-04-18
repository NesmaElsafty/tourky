<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWithFiredNotificationsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fired = $this->relationLoaded('fired_notifications')
            ? $this->fired_notifications
            : collect();

        return [
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'phone' => $this->phone,
                'email' => $this->email,
                'type' => $this->type,
            ],
            'fired_notifications' => FiredNotificationResource::collection($fired),
        ];
    }
}
