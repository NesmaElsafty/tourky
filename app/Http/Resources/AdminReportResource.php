<?php

namespace App\Http\Resources;

use App\Models\CaptainReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin view of a client report (list + detail).
 *
 * @mixin CaptainReport
 */
class AdminReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reservation = $this->reservation;
        $client = $reservation?->user;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type === CaptainReport::TYPE_TRIP
                ? __('api.reports.type_trip')
                : __('api.reports.type_captain'),
            'message' => $this->message,
            'trip_id' => $this->trip_id,
            'reservation_id' => $this->reservation_id,
            'trip' => $this->whenLoaded('trip', fn () => $this->trip === null ? null : [
                'id' => $this->trip->id,
                'date' => $this->trip->date,
                'status' => $this->trip->status,
            ]),
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
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
