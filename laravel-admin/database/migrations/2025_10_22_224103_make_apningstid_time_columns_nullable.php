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
        Schema::table('_apningstid', function (Blueprint $table) {
            // Make all time columns nullable so we can store NULL when a day is closed
            $table->string('ManStart', 11)->nullable()->change();
            $table->string('ManStopp', 11)->nullable()->change();
            $table->string('TirStart', 11)->nullable()->change();
            $table->string('TirStopp', 11)->nullable()->change();
            $table->string('OnsStart', 11)->nullable()->change();
            $table->string('OnsStopp', 11)->nullable()->change();
            $table->string('TorStart', 11)->nullable()->change();
            $table->string('TorStopp', 11)->nullable()->change();
            $table->string('FreStart', 11)->nullable()->change();
            $table->string('FreStopp', 11)->nullable()->change();
            $table->string('LorStart', 11)->nullable()->change();
            $table->string('LorStopp', 11)->nullable()->change();
            $table->string('SonStart', 11)->nullable()->change();
            $table->string('SonStopp', 11)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('_apningstid', function (Blueprint $table) {
            // Revert back to NOT NULL (requires default values)
            $table->string('ManStart', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('ManStopp', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('TirStart', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('TirStopp', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('OnsStart', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('OnsStopp', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('TorStart', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('TorStopp', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('FreStart', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('FreStopp', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('LorStart', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('LorStopp', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('SonStart', 11)->nullable(false)->default('00:00:00')->change();
            $table->string('SonStopp', 11)->nullable(false)->default('00:00:00')->change();
        });
    }
};
