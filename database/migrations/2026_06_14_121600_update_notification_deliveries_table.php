<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->dropForeign(['notification_id']);
        });

        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->unsignedBigInteger('notification_id')->nullable()->change();
            $table->foreign('notification_id')->references('id')->on('notifications')->nullOnDelete();
            $table->string('title_en')->nullable()->after('notification_id');
            $table->string('title_ar')->nullable()->after('title_en');
            $table->text('description_en')->nullable()->after('title_ar');
            $table->text('description_ar')->nullable()->after('description_en');
        });
    }

    public function down(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->dropForeign(['notification_id']);
            $table->dropColumn(['title_en', 'title_ar', 'description_en', 'description_ar']);
        });

        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->unsignedBigInteger('notification_id')->nullable(false)->change();
            $table->foreign('notification_id')->references('id')->on('notifications')->cascadeOnDelete();
        });
    }
};
