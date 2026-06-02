<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => User::query()->where('type', 'client')->inRandomOrder()->value('id'),
            'amount' => fake()->randomFloat(2, 50, 2500),
            'transaction_type' => fake()->randomElement(['manual', 'gateway']),
            'transaction_status' => fake()->randomElement(['pending', 'accepted', 'accepted', 'rejected']),
            'transaction_method' => fake()->randomElement(['cash', 'bank_transfer', 'wallet', 'online_payment']),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'transaction_status' => 'pending',
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'transaction_status' => 'accepted',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'transaction_status' => 'rejected',
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn (): array => [
            'transaction_type' => 'manual',
        ]);
    }

    public function gateway(): static
    {
        return $this->state(fn (): array => [
            'transaction_type' => 'gateway',
        ]);
    }

    public function forClient(User|int $client): static
    {
        $clientId = $client instanceof User ? $client->id : $client;

        return $this->state(fn (): array => [
            'client_id' => $clientId,
        ]);
    }
}
