<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SOLVED = 'solved';

    public const STATUS_CLOSED = 'closed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_SOLVED,
        self::STATUS_CLOSED,
    ];

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function captain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * @return HasMany<TicketMsg, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMsg::class)->orderBy('id');
    }

    public function hasAdminReply(): bool
    {
        return $this->messages()
            ->whereHas('user', static fn ($q) => $q->where('type', 'admin'))
            ->exists();
    }
}
