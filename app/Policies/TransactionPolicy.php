<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->type, ['client', 'admin'], true);
    }

    public function view(User $user, Transaction $transaction): bool
    {
        if ($user->type === 'admin') {
            return true;
        }

        return $user->type === 'client' && (int) $transaction->client_id === (int) $user->id;
    }

    public function createForClient(User $user): bool
    {
        return $user->type === 'client';
    }

    public function createForAdmin(User $user): bool
    {
        return $user->type === 'admin';
    }

    public function changeStatus(User $user, Transaction $transaction): bool
    {
        return $user->type === 'admin';
    }
}
