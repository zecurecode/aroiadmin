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
        Schema::create('apningstid', function (Blueprint $table) {
            $table->id();
            $table->string('day'); // Day of the week (Monday, Tuesday, etc.)

            // Namsos location
            $table->time('opennamsos')->nullable();
            $table->time('closenamsos')->nullable();
            $table->integer('statusnamsos')->default(0);
            $table->text('notesnamsos')->nullable();

            // Lade location
            $table->time('openlade')->nullable();
            $table->time('closelade')->nullable();
            $table->integer('statuslade')->default(0);
            $table->text('noteslade')->nullable();

            // Moan location
            $table->time('openmoan')->nullable();
            $table->time('closemoan')->nullable();
            $table->integer('statusmoan')->default(0);
            $table->text('notesmoan')->nullable();

            // Gramyra location
            $table->time('opengramyra')->nullable();
            $table->time('closegramyra')->nullable();
            $table->integer('statusgramyra')->default(0);
            $table->text('notesgramyra')->nullable();

            // Frosta location
            $table->time('openfrosta')->nullable();
            $table->time('closefrosta')->nullable();
            $table->integer('statusfrosta')->default(0);
            $table->text('notesfrosta')->nullable();

            // Hell location
            $table->time('openhell')->nullable();
            $table->time('closehell')->nullable();
            $table->integer('statushell')->default(0);
            $table->text('noteshell')->nullable();

            $table->timestamps();

            // Unique constraint on day
            $table->unique('day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apningstid');
    }
};
