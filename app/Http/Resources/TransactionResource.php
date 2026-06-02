<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->transaction_status;
        $method = $this->transaction_method;

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'amount' => $this->amount,
            'transaction_type' => $this->transaction_type,
            'transaction_status' => $status,
            'transaction_status_label' => $status !== null ? __('api.transactions.status_labels.'.$status) : null,
            'transaction_method' => $method,
            'transaction_method_label' => $method !== null ? __('api.transactions.method_labels.'.$method) : null,
            'image' => $this->getFirstMedia('image')?->getUrl(),
            'client' => $this->when(
                $this->relationLoaded('client') && $this->client !== null,
                fn () => [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'phone' => $this->client->phone,
                    'email' => $this->client->email,
                    'balance' => $this->client->balance,
                ]
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
