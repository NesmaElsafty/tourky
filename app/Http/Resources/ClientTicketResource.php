<?php

namespace App\Http\Resources;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ticket
 */
class ClientTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $trip = $this->trip;
        $adminReplied = isset($this->admin_replies_count)
            ? (int) $this->admin_replies_count > 0
            : $this->hasAdminReply();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => __('api.tickets.status_labels.'.$this->status),
            'captain' => $this->whenLoaded('captain', fn () => $this->captain === null ? null : [
                'id' => $this->captain->id,
                'name' => $this->captain->name,
                'phone' => $this->captain->phone,
            ]),
            'trip' => $this->when(
                $trip !== null && $this->relationLoaded('trip'),
                static fn () => [
                    'id' => $trip->id,
                    'date' => $trip->date,
                    'status' => $trip->status,
                    'pickup_time' => $trip->relationLoaded('time') && $trip->time !== null
                        ? $trip->time->pickup_time
                        : null,
                ]
            ),
            'messages' => TicketMsgResource::collection($this->whenLoaded('messages')),
            'can_edit' => ! $adminReplied,
            'can_delete' => ! $adminReplied,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
