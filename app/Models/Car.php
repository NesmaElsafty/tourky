<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return HasMany<TripCar, $this>
     */
    public function tripCars(): HasMany
    {
        return $this->hasMany(TripCar::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function captain()
    {
        return $this->belongsTo(User::class, 'captain_id');
    }
}
