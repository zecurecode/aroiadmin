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
        Schema::create('catering_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('site_id');
            $table->string('catering_email')->nullable();
            $table->boolean('catering_enabled')->default(true);
            $table->integer('min_guests')->default(10);
            $table->integer('advance_notice_days')->default(2);
            $table->decimal('min_order_amount', 10, 2)->default(1500.00);
            $table->text('catering_info')->nullable();
            $table->json('blocked_dates')->nullable();
            $table->timestamps();

            $table->index('site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catering_settings');
    }
};
