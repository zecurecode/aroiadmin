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
        Schema::table('orders', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('orders', 'pck_export_status')) {
                $table->enum('pck_export_status', ['new', 'sent', 'ack_failed'])->default('new')->after('paymentmethod');
            }
            
            if (!Schema::hasColumn('orders', 'pck_exported_at')) {
                $table->timestamp('pck_exported_at')->nullable()->after('pck_export_status');
            }
            
            if (!Schema::hasColumn('orders', 'pck_last_error')) {
                $table->text('pck_last_error')->nullable()->after('pck_exported_at');
            }
        });

        // Add index separately to avoid issues with column positioning
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['site', 'pck_export_status', 'datetime'], 'orders_pck_export_index');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore the error
            \Log::info('PCK export index might already exist: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop index if it exists
            try {
                $table->dropIndex('orders_pck_export_index');
            } catch (\Exception $e) {
                // Index might not exist, ignore
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            // Only drop columns if they exist
            if (Schema::hasColumn('orders', 'pck_export_status')) {
                $table->dropColumn('pck_export_status');
            }
            
            if (Schema::hasColumn('orders', 'pck_exported_at')) {
                $table->dropColumn('pck_exported_at');
            }
            
            if (Schema::hasColumn('orders', 'pck_last_error')) {
                $table->dropColumn('pck_last_error');
            }
        });
    }
};