<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * @param  array{
     *     type: string,
     *     search?: string|null,
     *     language?: string|null,
     *     role_id?: int|string|null,
     *     created_from?: string|null,
     *     created_to?: string|null,
     *     per_page?: int,
     *     only_trashed?: bool,
     *     for_company_owner_id?: int|null
     * }  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateUsers(array $filters): LengthAwarePaginator
    {
        $type = $filters['type'];
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        $onlyTrashed = (bool) ($filters['only_trashed'] ?? false);

        $query = User::query()
            ->with(['role'])
            ->where('type', $type);

        if ($onlyTrashed) {
            $query->onlyTrashed();
        } else {
            $query->whereNull('deleted_at');
        }

        $this->applyListFilters($query, $filters);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyListFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search']) && is_string($filters['search'])) {
            $term = trim($filters['search']);
            if ($term !== '') {
                $escaped = addcslashes($term, '%_\\');
                $pattern = '%'.$escaped.'%';
                $query->where(function (Builder $q) use ($pattern): void {
                    $q->where('name', 'like', $pattern)
                        ->orWhere('phone', 'like', $pattern)
                        ->orWhere('email', 'like', $pattern);
                });
            }
        }

        if (! empty($filters['language']) && in_array($filters['language'], ['en', 'ar'], true)) {
            $query->where('language', $filters['language']);
        }

        if (($filters['type'] ?? '') === 'admin' && isset($filters['role_id']) && $filters['role_id'] !== '' && $filters['role_id'] !== null) {
            $query->where('role_id', (int) $filters['role_id']);
        }

        if (($filters['type'] ?? '') === 'client' && ! empty($filters['for_company_owner_id'])) {
            $query->where('company_id', (int) $filters['for_company_owner_id']);
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }
    }

    public function findUserForAdmin(int $id, bool $withTrashed = true): User
    {
        $query = User::query()->with(['role']);
        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->findOrFail($id);
    }

    public function findActiveUserForAdmin(int $id): User
    {
        return User::query()->with(['role'])->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): User
    {
        return User::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateUser(User $user, array $data): User
    {
        if ($user->trashed()) {
            throw ValidationException::withMessages([
                'user' => [__('api.users.cannot_update_blocked')],
            ]);
        }

        $user->update($data);

        return $user->fresh(['role']) ?? $user;
    }

    public function softDeleteUser(User $user, User $actor): void
    {
        if ((int) $user->id === (int) $actor->id) {
            throw ValidationException::withMessages([
                'user' => [__('api.users.cannot_delete_self')],
            ]);
        }

        if ($user->trashed()) {
            throw ValidationException::withMessages([
                'user' => [__('api.users.already_blocked')],
            ]);
        }

        $user->delete();
    }

    public function restoreUser(int $id): User
    {
        $user = User::onlyTrashed()->with(['role'])->findOrFail($id);

        $user->restore();

        return $user->fresh(['role']) ?? $user;
    }
}
