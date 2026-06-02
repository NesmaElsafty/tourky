<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Transaction extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    public const TYPES = ['manual', 'gateway'];

    public const STATUSES = ['pending', 'accepted', 'rejected'];

    public const METHODS = ['cash', 'bank_transfer', 'wallet', 'online_payment'];

    public const CLIENT_METHODS = ['bank_transfer', 'wallet'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Transaction $transaction): void {
            static::syncClientBalance($transaction->client_id);
        });

        static::deleted(function (Transaction $transaction): void {
            static::syncClientBalance($transaction->client_id);
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public static function syncClientBalance(int $clientId): void
    {
        $balance = static::query()
            ->where('client_id', $clientId)
            ->where('transaction_status', 'accepted')
            ->sum('amount');

        User::query()
            ->where('id', $clientId)
            ->where('type', 'client')
            ->update(['balance' => $balance]);
    }

    public function registerMediaCollections(): void
    {
            $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);
    }
}
