<?php

namespace App\Models;

use Database\Factories\NotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    /** @use HasFactory<NotificationDeliveryFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function isDirectDelivery(): bool
    {
        return $this->notification_id === null;
    }

    public function localizedTitle(string $locale): ?string
    {
        if (! $this->isDirectDelivery()) {
            return null;
        }

        return $this->localizedField('title_en', 'title_ar', $locale);
    }

    public function localizedDescription(string $locale): ?string
    {
        if (! $this->isDirectDelivery()) {
            return null;
        }

        return $this->localizedField('description_en', 'description_ar', $locale);
    }

    /**
     * @return BelongsTo<Notification, $this>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    private function localizedField(string $enKey, string $arKey, string $locale): ?string
    {
        $en = $this->{$enKey};
        $ar = $this->{$arKey};

        if ($locale === 'ar') {
            return $ar ?: $en;
        }

        return $en ?: $ar;
    }
}
