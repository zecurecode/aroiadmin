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
        Schema::create('special_hours', function (Blueprint $table) {
            $table->id();
            $table->integer('location_id'); // AvdID reference
            $table->date('date'); // Specific date
            $table->date('end_date')->nullable(); // For date ranges (periods)
            $table->time('open_time')->nullable(); // Custom opening time (null = closed)
            $table->time('close_time')->nullable(); // Custom closing time
            $table->boolean('is_closed')->default(false); // Explicitly closed
            $table->string('reason')->nullable(); // Reason for closure/special hours
            $table->string('type')->default('special'); // special, holiday, maintenance, etc.
            $table->boolean('recurring_yearly')->default(false); // For holidays that repeat yearly
            $table->text('notes')->nullable();
            $table->integer('created_by')->nullable(); // User who created this
            $table->timestamps();

            // Indexes for performance
            $table->index(['location_id', 'date']);
            $table->index(['date', 'end_date']);
            $table->unique(['location_id', 'date']); // Prevent duplicate entries for same location/date
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_hours');
    }
};
