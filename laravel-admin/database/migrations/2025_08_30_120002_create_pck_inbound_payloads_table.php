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
        Schema::create('pck_inbound_payloads', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->enum('method', [
                'sendArticle',
                'sendProductLine', 
                'sendImage',
                'sendImageColor',
                'sendArticleGroup',
                'sendManufacturer',
                'sendSize',
                'sendColor',
                'updateStockCount',
                'removeArticle',
                'sendDiscount',
                'sendCustomerInfo'
            ]);
            $table->string('idempotency_key', 255)->unique();
            $table->json('payload');
            $table->enum('status', ['received', 'processed', 'failed'])->default('received');
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->json('error')->nullable();
            $table->timestamps();

            // Indexes for efficient processing
            $table->index(['tenant_id', 'status', 'received_at']);
            $table->index(['tenant_id', 'method', 'status']);
            $table->index('idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pck_inbound_payloads');
    }
};