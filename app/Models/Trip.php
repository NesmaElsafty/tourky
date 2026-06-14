<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function time(): BelongsTo
    {
        return $this->belongsTo(Time::class);
    }

    public function routeTime(): BelongsTo
    {
        return $this->belongsTo(RouteTime::class);
    }

    public function tripCars(): HasMany
    {
        return $this->hasMany(TripCar::class)->orderBy('id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CaptainReport::class);
    }

    public function trackTrips(): HasMany
    {
        return $this->hasMany(TrackTrip::class)->orderByDesc('created_at');
    }

    public function captainsWithCurrentTrip(): HasMany
    {
        return $this->hasMany(User::class, 'trip_id');
    }
}
