<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    public function getNotificationsPaginated(int $perPage = 10, ?string $userType = null): LengthAwarePaginator
    {
        return Notification::query()
            ->when(
                $userType !== null && $userType !== '',
                fn ($query) => $query->where('user_type', $userType),
            )
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getNotificationsForUserType(string $userType): Collection
    {
        return Notification::query()
            ->where('user_type', $userType)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return LengthAwarePaginator<int, NotificationDelivery>
     */
    public function getFiredNotificationDeliveriesForUserPaginated(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return NotificationDelivery::query()
            ->where('user_id', $user->id)
            ->with('notification')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getUnreadDeliveriesCountForUser(User $user): int
    {
        return NotificationDelivery::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markDeliveryAsReadForUser(User $user, int $deliveryId): bool
    {
        $delivery = NotificationDelivery::query()
            ->where('id', $deliveryId)
            ->where('user_id', $user->id)
            ->first();

        if ($delivery === null) {
            return false;
        }

        if ($delivery->read_at === null) {
            $delivery->forceFill(['read_at' => now()])->save();
        }

        return true;
    }

    public function markAllDeliveriesAsReadForUser(User $user): int
    {
        return NotificationDelivery::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function getNotificationById(int $id): Notification
    {
        return Notification::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createNotification(array $data): Notification
    {
        return Notification::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateNotification(Notification $notification, array $data): Notification
    {
        $notification->update($data);

        return $notification->fresh();
    }

    public function deleteNotification(Notification $notification): void
    {
        $notification->delete();
    }

    /**
     * Push a notification template to all users of the same portal type (or a subset by id).
     *
     * @param  list<int>|null  $onlyUserIds
     */
    public function fireNotification(Notification $notification, ?array $onlyUserIds = null): int
    {
        $query = User::query()->where('type', $notification->user_type);

        if ($onlyUserIds !== null && $onlyUserIds !== []) {
            $onlyUserIds = array_values(array_unique(array_map('intval', $onlyUserIds)));
            $wrongType = User::query()
                ->whereIn('id', $onlyUserIds)
                ->where('type', '!=', $notification->user_type)
                ->exists();
            if ($wrongType) {
                throw ValidationException::withMessages([
                    'user_ids' => [__('api.notifications.fire_invalid_users')],
                ]);
            }
            $query->whereIn('id', $onlyUserIds);
        }

        $ids = $query->pluck('id')->all();

        if ($ids === []) {
            return 0;
        }

        $now = now();
        $rows = array_map(static fn (int $userId): array => [
            'notification_id' => $notification->id,
            'user_id' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $ids);

        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, 500) as $chunk) {
                NotificationDelivery::query()->insert($chunk);
            }
        });

        return count($ids);
    }

    /**
     * Paginate users of a portal type, each with their fired notification deliveries (newest first).
     *
     * @param  'client'|'captain'  $userType
     * @return LengthAwarePaginator<int, User>
     */
    public function getUsersWithFiredNotificationsPaginated(string $userType, int $perPage = 10): LengthAwarePaginator
    {
        $users = User::query()
            ->select(['id', 'name', 'phone', 'email', 'type'])
            ->where('type', $userType)
            ->orderBy('id')
            ->paginate($perPage);

        $ids = $users->getCollection()->pluck('id');
        if ($ids->isEmpty()) {
            return $users;
        }

        $deliveries = NotificationDelivery::query()
            ->whereIn('user_id', $ids->all())
            ->with('notification')
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id');

        foreach ($users as $user) {
            $user->setRelation(
                'fired_notifications',
                $deliveries->get($user->id, collect()),
            );
        }

        return $users;
    }
}
