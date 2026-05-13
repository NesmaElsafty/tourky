<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TicketMsg;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Deterministic demo rows for tickets / ticket_msgs (safe to re-run).
     */
    private const TITLE_PREFIX = '[SEED]';

    public function run(): void
    {
        Ticket::query()->where('title', 'like', self::TITLE_PREFIX.'%')->delete();

        $admin = User::query()
            ->where('phone', '01000000001')
            ->where('type', 'admin')
            ->first();

        $client = User::query()
            ->where('type', 'client')
            ->orderBy('id')
            ->first();

        if ($admin === null || $client === null) {
            $this->command?->warn('TicketSeeder skipped: need admin user (phone 01000000001, type admin) and at least one client user.');

            return;
        }

        $captain = User::query()
            ->where('type', 'captain')
            ->orderBy('id')
            ->first();

        $trip = Trip::query()->orderBy('id')->first();

        Ticket::query()->create([
            'title' => self::TITLE_PREFIX.' Demo — pending, no admin reply',
            'description' => 'Seeded ticket: client can still update or delete until an admin posts a message.',
            'status' => Ticket::STATUS_PENDING,
            'client_id' => $client->id,
            'captain_id' => $captain?->id,
            'trip_id' => $trip?->id,
        ]);

        $locked = Ticket::query()->create([
            'title' => self::TITLE_PREFIX.' Demo — admin replied (locked for client)',
            'description' => 'Seeded ticket: includes an admin message so edit/delete should be rejected for the client.',
            'status' => Ticket::STATUS_IN_PROGRESS,
            'client_id' => $client->id,
            'captain_id' => $captain?->id,
            'trip_id' => $trip?->id,
        ]);

        TicketMsg::query()->create([
            'ticket_id' => $locked->id,
            'user_id' => $admin->id,
            'message' => self::TITLE_PREFIX.' Admin reply: we received your ticket and are reviewing it.',
        ]);

        Ticket::query()->create([
            'title' => self::TITLE_PREFIX.' Demo — for admin reply POST',
            'description' => 'Seeded ticket with no messages yet: use POST .../reply in Postman without breaking the editable demo row.',
            'status' => Ticket::STATUS_PENDING,
            'client_id' => $client->id,
            'captain_id' => $captain?->id,
            'trip_id' => $trip?->id,
        ]);
    }
}
