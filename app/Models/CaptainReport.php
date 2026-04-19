<?php

namespace App\Models;

use Database\Factories\CaptainReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaptainReport extends Model
{
    /** @use HasFactory<CaptainReportFactory> */
    use HasFactory;

    public const TYPE_TRIP = 'trip';

    public const TYPE_CAPTAIN = 'captain';

    protected $guarded = [];

    /**
     * @param  Builder<CaptainReport>  $query
     * @return Builder<CaptainReport>
     */
    public function scopeCaptainSubject(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_CAPTAIN);
    }

    /**
     * @param  Builder<CaptainReport>  $query
     * @return Builder<CaptainReport>
     */
    public function scopeTripSubject(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_TRIP);
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function captain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    /**
     * Admin user who replied.
     *
     * @return BelongsTo<User, $this>
     */
    public function repliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
        ];
    }
}
