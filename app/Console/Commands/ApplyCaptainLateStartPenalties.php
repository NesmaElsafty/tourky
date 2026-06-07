<?php

namespace App\Console\Commands;

use App\Services\CaptainLateStartPenaltyService;
use Illuminate\Console\Command;

class ApplyCaptainLateStartPenalties extends Command
{
    protected $signature = 'captain:apply-late-start-penalties';

    protected $description = 'Apply late-start penalties and cancel unstarted planned trips after grace periods';

    public function handle(CaptainLateStartPenaltyService $penaltyService): int
    {
        $result = $penaltyService->processOverduePlannedTrips();

        $this->info("Applied {$result['penalties_applied']} late-start penalty(ies).");
        $this->info("Cancelled {$result['trips_cancelled']} overdue unstarted trip(s).");

        return self::SUCCESS;
    }
}
