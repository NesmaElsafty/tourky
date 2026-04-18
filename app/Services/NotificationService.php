<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * All notifications for a portal (client or captain).
     */
    public function getNotificationsForUserType(string $userType): Collection
    {
        return Notification::query()
            ->where('user_type', $userType)
            ->orderByDesc('id')
            ->get();
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
}
