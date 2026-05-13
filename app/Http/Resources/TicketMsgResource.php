<?php

namespace App\Http\Resources;

use App\Models\TicketMsg;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TicketMsg
 */
class TicketMsgResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'message' => $this->message,
            'sender' => $this->when(
                $user !== null,
                fn () => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'type' => $user->type,
                ]
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
