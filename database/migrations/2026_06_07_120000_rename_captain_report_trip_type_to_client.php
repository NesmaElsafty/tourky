<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('captain_reports')
            ->where('type', 'trip')
            ->update(['type' => 'client']);
    }

    public function down(): void
    {
        DB::table('captain_reports')
            ->where('type', 'client')
            ->update(['type' => 'trip']);
    }
};
