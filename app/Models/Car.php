<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
