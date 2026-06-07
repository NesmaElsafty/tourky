<?php

namespace App\Http\Resources;

use App\Models\CaptainReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Captain-facing rejection report (includes admin reply).
 *
 * @mixin CaptainReport
 */
class CaptainRejectionReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reservation = $this->reservation;
        $client = $reservation?->user;

        return [
            'id' => $this->id,
            'message' => $this->message,
            'reservation_id' => $this->reservation_id,
            'client' => $this->when(
                $client !== null,
                fn () => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'phone' => $client->phone,
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
}
