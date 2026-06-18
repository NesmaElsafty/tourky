<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesApiLocale;
use App\Services\CaptainDocumentService;
use App\Models\Reservation;
use App\Models\TripCar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    use ResolvesApiLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $bookedTrips = 0;
        if ($this->type === 'client') {
            $bookedTrips = Reservation::where('user_id', $this->id)->where('status', 'confirmed')->whereHas('trip', function ($query) {
                $query->where('status', 'completed');
            })->count();
        }

        if($this->type === 'captain') {
            $bookedTrips = TripCar::where('captain_id', $this->id)->whereHas('trip', function ($query) {
                $query->where('status', 'completed');
            })->count();
        }
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'type' => $this->type,
            'type_label' => $this->type !== null
                ? __('api.users.type_labels.'.$this->type, [], $locale)
                : null,
            'language' => $this->language,
            'is_blocked' => $this->trashed(),
            'blocked_at' => $this->deleted_at,
            'role' => $this->when(
                $this->relationLoaded('role') && $this->role !== null && $this->type === 'admin',
                fn () => [
                    'id' => $this->role->id,
                    'name' => $locale === 'ar'
                        ? ($this->role->name_ar ?? $this->role->name_en)
                        : ($this->role->name_en ?? $this->role->name_ar),
                    'name_en' => $this->role->name_en,
                    'name_ar' => $this->role->name_ar,
                ],
            ),

            'booked_trips' => $bookedTrips ?? 0,
            'balance' => $this->balance,
            'role_id' => $this->when($this->type === 'admin', $this->role_id),
            'rating_average' => $this->when(
                $this->type === 'captain' && $this->resource->offsetExists('captain_rating_average'),
                fn () => $this->resource->getAttribute('captain_rating_average'),
            ),
            'ratings_count' => $this->when(
                $this->type === 'captain' && $this->resource->offsetExists('captain_ratings_count'),
                fn () => $this->resource->getAttribute('captain_ratings_count'),
            ),
            'feedback_entries' => $this->when(
                $this->type === 'captain' && $this->resource->offsetExists('captain_feedback_entries'),
                fn () => $this->resource->getAttribute('captain_feedback_entries'),
            ),
            'lat' => $this->when($this->type === 'captain', $this->lat),
            'long' => $this->when($this->type === 'captain', $this->long),
            'status' => $this->when($this->type === 'captain', $this->status),
            'has_trip' => $this->when($this->type === 'captain', $this->has_trip),
            'trip_id' => $this->when($this->type === 'captain', $this->trip_id),
            'license_expiry_date' => $this->when(
                $this->type === 'captain',
                fn () => $this->license_expiry_date?->format('Y-m-d'),
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'display_locale' => $locale,
            'image' => $this->when(
                $this->type === 'captain',
                fn () => $this->getMedia('image')->first()?->getUrl(),
            ),
            'documents' => $this->when(
                $this->type === 'captain',
                fn () => app(CaptainDocumentService::class)->toResourceArray($this->resource, $locale),
            ),
            'car' => $this->when($this->relationLoaded('car'), fn () => new CarResource($this->car)),
        ];
    }
}
