<?php

namespace App\Http\Resources;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ticket
 */
class AdminTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $trip = $this->trip;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => __('api.tickets.status_labels.'.$this->status),
            'captain_id' => $this->captain_id,
            'trip_id' => $this->trip_id,
            'client_id' => $this->client_id,
            'client' => $this->whenLoaded('client', fn () => $this->client === null ? null : [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'phone' => $this->client->phone,
                'email' => $this->client->email,
            ]),
            'captain' => $this->whenLoaded('captain', fn () => $this->captain === null ? null : [
                'id' => $this->captain->id,
                'name' => $this->captain->name,
                'phone' => $this->captain->phone,
            ]),
            'trip' => $this->when(
                $trip !== null && $this->relationLoaded('trip'),
                function () use ($trip) {
                    $row = [
                        'id' => $trip->id,
                        'date' => $trip->date,
                        'status' => $trip->status,
                    ];
                    if ($trip->relationLoaded('time') && $trip->time !== null) {
                        $row['pickup_time'] = $trip->time->pickup_time;
                    }
                    if ($trip->relationLoaded('tripCars')) {
                        $row['vehicles'] = $trip->tripCars->map(static fn ($tc) => [
                            'captain' => $tc->relationLoaded('captain') && $tc->captain !== null
                                ? ['id' => $tc->captain->id, 'name' => $tc->captain->name, 'phone' => $tc->captain->phone]
                                : null,
                            'car' => $tc->relationLoaded('car') && $tc->car !== null
                                ? [
                                    'id' => $tc->car->id,
                                    'type' => $tc->car->type,
                                    'plate_numbers' => $tc->car->plate_numbers,
                                    'plate_letters' => $tc->car->plate_letters,
                                ]
                                : null,
                        ]);
                    }

                    return $row;
                }
            ),
            'messages' => TicketMsgResource::collection($this->whenLoaded('messages')),
            'admin_has_replied' => $this->when(
                isset($this->admin_replies_count),
                (int) $this->admin_replies_count > 0
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
