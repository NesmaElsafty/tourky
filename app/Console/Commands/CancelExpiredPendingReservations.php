<?php

namespace App\Console\Commands;

use App\Services\ReservationExpiryService;
use Illuminate\Console\Command;

class CancelExpiredPendingReservations extends Command
{
    protected $signature = 'reservations:cancel-expired-pending';

    protected $description = 'Cancel pending reservations whose pickup date and time are in the past';

    public function handle(ReservationExpiryService $expiryService): int
    {
        $cancelled = $expiryService->cancelExpiredPendingReservations();

        $this->info("Cancelled {$cancelled} expired pending reservation(s).");

        return self::SUCCESS;
    }
}
