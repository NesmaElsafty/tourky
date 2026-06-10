<?php

namespace App\Models;

use App\Services\FcmNotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    use HasFactory;

    public const USER_TYPES = ['client', 'captain'];

    protected $table = 'notifications';

    protected $guarded = [];

    /**
     * @return HasMany<NotificationDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function pushToUser(User $user): bool
    {
        return app(FcmNotificationService::class)->sendNotificationToUser($user, $this);
    }

    /**
     * @param  Collection<int, User>|list<User>  $users
     */
    public function pushToUsers(Collection|array $users): int
    {
        return app(FcmNotificationService::class)->sendNotificationToUsers($users, $this);
    }
}
