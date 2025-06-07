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
        Schema::table('users', function (Blueprint $table) {
            // Add required columns for Aroi system
            $table->string('username')->unique()->after('id');
            $table->integer('siteid')->nullable()->after('username');
            $table->integer('license')->nullable()->after('siteid');

            // Make email nullable since we use username authentication
            $table->string('email')->nullable()->change();
            $table->timestamp('email_verified_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'siteid', 'license']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
