<?php

namespace App\Services;

use App\Models\Term;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TermService
{
    public function getTermsPaginated(int $perPage = 10, bool $onlyActive = true): LengthAwarePaginator
    {
        return Term::query()
            ->when($onlyActive, fn ($query) => $query->where('is_active', true))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Active terms for a portal (client or captain).
     */
    public function getActiveTermsForUserType(string $userType, string $type): Collection
    {
        return Term::query()
            ->where('is_active', true)
            ->where('user_type', $userType)
            ->where('type', $type)
            ->orderBy('id')
            ->get();
    }

    // get all terms for a portal (client or captain).
    public function getAllTermsForUserType(string $userType, string $type): Collection
    {
        return Term::query()
            ->where('user_type', $userType)
            ->where('type', $type)
            ->orderBy('id')
            ->get();
    }

    public function getTermById(int $id): Term
    {
        return Term::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTerm(array $data): Term
    {
        return Term::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTerm(Term $term, array $data): Term
    {
        $term->update($data);

        return $term->fresh();
    }

    public function deleteTerm(Term $term): void
    {
        $term->delete();
    }
}
