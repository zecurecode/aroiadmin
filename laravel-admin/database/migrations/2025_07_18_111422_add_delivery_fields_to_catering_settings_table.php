<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('catering_settings', function (Blueprint $table) {
            $table->json('delivery_times')->nullable()->after('blocked_dates');
            $table->json('delivery_areas')->nullable()->after('delivery_times');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catering_settings', function (Blueprint $table) {
            $table->dropColumn(['delivery_times', 'delivery_areas']);
        });
    }
};
