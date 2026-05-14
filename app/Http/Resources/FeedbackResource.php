<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'feedback' => $this->feedback,
            'rating' => (int) $this->rating,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ]),
            'captain' => $this->whenLoaded('captain', fn () => [
                'id' => $this->captain->id,
                'name' => $this->captain->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
