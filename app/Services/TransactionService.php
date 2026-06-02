<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function getClientTransactionsPaginated(User $client, int $perPage = 10): LengthAwarePaginator
    {
        return Transaction::query()
            ->where('client_id', $client->id)
            ->with('client:id,name,phone,email,type,balance')
            ->latest('id')
            ->paginate($perPage);
    }

    public function getAdminTransactionsPaginated(?string $status, int $perPage = 10): LengthAwarePaginator
    {
        return Transaction::query()
            ->when(
                $status !== null,
                fn ($query) => $query->where('transaction_status', $status)
            )
            ->with('client:id,name,phone,email,type,balance')
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @param  array{amount: numeric-string|int|float, transaction_method: string}  $data
     */
    public function createForClient(User $client, array $data): Transaction
    {
        return Transaction::query()->create([
            'client_id' => $client->id,
            'amount' => $data['amount'],
            'transaction_type' => 'manual',
            'transaction_status' => 'pending',
            'transaction_method' => $data['transaction_method'],
        ]);
    }

    /**
     * @param  array{client_id: int, amount: numeric-string|int|float, transaction_status: string, transaction_method: string, transaction_type?: string}  $data
     */
    public function createForAdmin(array $data): Transaction
    {
        $client = User::query()
            ->where('type', 'client')
            ->find($data['client_id']);

        if ($client === null) {
            throw ValidationException::withMessages([
                'client_id' => [__('api.transactions.validation_client_invalid')],
            ]);
        }

        return Transaction::query()->create([
            'client_id' => $client->id,
            'amount' => $data['amount'],
            'transaction_type' => $data['transaction_type'] ?? 'manual',
            'transaction_status' => $data['transaction_status'],
            'transaction_method' => $data['transaction_method'],
        ]);
    }

    public function changePendingStatus(Transaction $transaction, string $status): Transaction
    {
        if ($transaction->transaction_status !== 'pending') {
            throw ValidationException::withMessages([
                'transaction' => [__('api.transactions.pending_only_status_change')],
            ]);
        }

        $transaction->update([
            'transaction_status' => $status,
        ]);

        return $transaction->fresh(['client:id,name,phone,email,type,balance']) ?? $transaction;
    }
}
