<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'type' => $this->type,
            'language' => $locale,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function resolveLocale(Request $request): string
    {
        $userLanguage = strtolower((string) ($this->language ?? ''));
        if ($userLanguage === 'en' || $userLanguage === 'ar') {
            return $userLanguage;
        }

        $headerLanguage = strtolower((string) $request->header('lang', ''));
        return $headerLanguage === 'ar' ? 'ar' : 'en';
    }
}
