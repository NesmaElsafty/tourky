<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $captainIdsByReportId = DB::table('captain_reports')
            ->select('id', 'captain_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->id => $row->captain_id])
            ->all();

        Schema::table('captain_reports', function (Blueprint $table): void {
            $table->dropForeign(['reservation_id']);
        });

        Schema::table('captain_reports', function (Blueprint $table): void {
            $table->dropUnique(['reservation_id']);
        });

        Schema::table('captain_reports', function (Blueprint $table): void {
            $table->string('type', 20)->default('captain')->after('id');
            $table->text('admin_reply')->nullable()->after('message');
            $table->timestamp('replied_at')->nullable()->after('admin_reply');
            $table->foreignId('replied_by')->nullable()->after('replied_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('captain_reports', function (Blueprint $table): void {
            $table->dropForeign(['captain_id']);
        });

        Schema::table('captain_reports', function (Blueprint $table): void {
            $table->dropColumn('captain_id');
        });

        Schema::table('captain_reports', function (Blueprint $table): void {
            $table->foreignId('captain_id')->nullable()->after('trip_id')->constrained('users')->cascadeOnDelete();
        });

        foreach ($captainIdsByReportId as $reportId => $captainId) {
            DB::table('captain_reports')->where('id', $reportId)->update([
                'captain_id' => $captainId,
            ]);
        }

        Schema::table('captain_reports', function (Blueprint $table): void {
            $table->unique(['reservation_id', 'type']);
            $table->index('type');
        });

        Schema::table('captain_reports', function (Blueprint $table): void {
            $table->foreign('reservation_id')->references('id')->on('reservations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('This migration cannot be safely reverted.');
    }
};
