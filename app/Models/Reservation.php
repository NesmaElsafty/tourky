<?php

namespace App\Models;

use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Route, $this>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * @return BelongsTo<Point, $this>
     */
    public function point(): BelongsTo
    {
        return $this->belongsTo(Point::class);
    }

    /**
     * @return BelongsTo<Time, $this>
     */
    public function time(): BelongsTo
    {
        return $this->belongsTo(Time::class);
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * @return BelongsTo<TripCar, $this>
     */
    public function tripCar(): BelongsTo
    {
        return $this->belongsTo(TripCar::class, 'trip_car_id');
    }
}
