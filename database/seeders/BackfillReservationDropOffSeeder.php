<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ResolvesReservationDropOff;
use Illuminate\Database\Seeder;

class BackfillReservationDropOffSeeder extends Seeder
{
    use ResolvesReservationDropOff;

    public function run(): void
    {
        $this->backfillReservationsMissingDropOffTimeId();
    }
}
