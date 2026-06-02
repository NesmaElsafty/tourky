<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $clients = User::query()
            ->where('type', 'client')
            ->orderBy('id')
            ->get();

        if ($clients->isEmpty()) {
            $this->command?->warn('No clients found; skipping TransactionSeeder.');

            return;
        }

        $demoClient = $clients->firstWhere('phone', '01000000003') ?? $clients->first();

        Transaction::query()->create([
            'client_id' => $demoClient->id,
            'amount' => 500.00,
            'transaction_type' => 'manual',
            'transaction_status' => 'accepted',
            'transaction_method' => 'bank_transfer',
        ]);

        Transaction::query()->create([
            'client_id' => $demoClient->id,
            'amount' => 200.00,
            'transaction_type' => 'gateway',
            'transaction_status' => 'accepted',
            'transaction_method' => 'online_payment',
        ]);

        Transaction::query()->create([
            'client_id' => $demoClient->id,
            'amount' => 150.00,
            'transaction_type' => 'manual',
            'transaction_status' => 'pending',
            'transaction_method' => 'cash',
        ]);

        Transaction::query()->create([
            'client_id' => $demoClient->id,
            'amount' => 75.00,
            'transaction_type' => 'manual',
            'transaction_status' => 'rejected',
            'transaction_method' => 'wallet',
        ]);

        foreach ($clients as $client) {
            if ($client->id === $demoClient->id) {
                continue;
            }

            Transaction::factory()
                ->count(fake()->numberBetween(2, 5))
                ->forClient($client)
                ->create();
        }

        foreach ($clients as $client) {
            Transaction::syncClientBalance($client->id);
        }
    }
}
