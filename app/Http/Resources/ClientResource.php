<?php

namespace App\Http\Resources;
use App\Http\Resources\ClientResource as ClientResourceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class ClientResource extends JsonResource
{
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
            'company' => $this->parentCompany ? [
                'id' => $this->parentCompany->id,
                'name' => $this->parentCompany->name,
                'client_type' => 'b2b',
            ] : null,
            
            'child_clients' => $this->childClients->map(function (User $client) {
                return new ClientResourceResource($client);
            }),
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
