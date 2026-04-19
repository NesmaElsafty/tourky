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

    /**
     * @return BelongsTo<Time, $this>
     */
    public function time(): BelongsTo
    {
        return $this->belongsTo(Time::class);
    }

    /**
     * @return HasMany<TripCar, $this>
     */
    public function tripCars(): HasMany
    {
        return $this->hasMany(TripCar::class)->orderBy('id');
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @return HasMany<CaptainReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(CaptainReport::class);
    }
}
