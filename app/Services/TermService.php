<?php

namespace App\Services;

use App\Models\Term;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TermService
{
    public function getTermsPaginated(int $perPage = 10, bool $onlyActive = true)
    {
        return Term::query()
            ->when($onlyActive, fn ($query) => $query->where('is_active', true))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Active terms for a portal (client or captain).
     */
    public function getActiveTermsForUserType(string $userType, string $type)
    {
        $terms = Term::query();
        $terms->where('is_active', true)
            ->where('user_type', $userType)
            ->where('type', $type)
            ->orderBy('id');
        return $terms;
    }

    // get all terms for a portal (client or captain).
    public function getAllTermsForUserType(string $userType, string $type)
    {
        $terms = Term::query();
        $terms->where('user_type', $userType)
            ->where('type', $type)
            ->orderBy('id');
        return $terms;
    }

    public function getTermById(int $id)
    {
        return Term::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTerm(array $data)
    {
        return Term::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTerm(Term $term, array $data)
    {
        $term->update($data);

        return $term->fresh();
    }

    public function deleteTerm(Term $term): void
    {
        $term->delete();
    }
}
