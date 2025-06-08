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
            // Add required columns for Aroi system only if they don't exist
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->after('id');
            }

            if (!Schema::hasColumn('users', 'siteid')) {
                $table->integer('siteid')->nullable()->after('username');
            }

            if (!Schema::hasColumn('users', 'license')) {
                $table->integer('license')->nullable()->after('siteid');
            }

            // Only modify email column if it exists
            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->change();
            }

            // Only modify email_verified_at column if it exists
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'siteid', 'license']);

            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable(false)->change();
            }
        });
    }
};
