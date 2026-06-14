<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('track_trips', function (Blueprint $table): void {
            $table->dropColumn(['event', 'note']);
        });
    }

    public function down(): void
    {
        Schema::table('track_trips', function (Blueprint $table): void {
            $table->string('event', 32)->nullable()->after('client_id');
            $table->text('note')->nullable()->after('event');
        });
    }
};
