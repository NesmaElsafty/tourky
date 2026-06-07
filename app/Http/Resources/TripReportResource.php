<?php

namespace App\Http\Resources;

use App\Models\CaptainReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Report embedded in admin trip detail responses.
 *
 * @mixin CaptainReport
 */
class TripReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reservation = $this->reservation;
        $client = $reservation?->user;

        $data = [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type === CaptainReport::TYPE_CLIENT
                ? __('api.reports.type_client')
                : __('api.reports.type_captain'),
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
            'captain' => $this->when(
                $this->type === CaptainReport::TYPE_CAPTAIN
                    && $this->relationLoaded('captain')
                    && $this->captain !== null,
                fn () => [
                    'id' => $this->captain->id,
                    'name' => $this->captain->name,
                    'phone' => $this->captain->phone,
                ]
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];

        if ($this->type === CaptainReport::TYPE_CAPTAIN) {
            $data['admin_reply'] = $this->admin_reply;
            $data['replied_at'] = $this->replied_at?->toIso8601String();
            $data['replied_by'] = $this->when(
                $this->relationLoaded('repliedByUser') && $this->repliedByUser !== null,
                fn () => [
                    'id' => $this->repliedByUser->id,
                    'name' => $this->repliedByUser->name,
                ]
            );
        }

        return $data;
    }
}
