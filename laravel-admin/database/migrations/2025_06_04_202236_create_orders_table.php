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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('fornavn'); // First name
            $table->string('etternavn'); // Last name
            $table->string('telefon'); // Phone number
            $table->integer('ordreid'); // Order ID from WooCommerce
            $table->integer('ordrestatus')->default(0); // Order status (0=pending, 1=processing, 2=ready, 3=complete)
            $table->string('epost'); // Email
            $table->integer('curl')->default(0); // POS system status (0=not sent, 200/201=sent)
            $table->integer('site'); // Location site ID
            $table->boolean('paid')->default(false); // Payment status
            $table->boolean('sms')->default(false); // SMS sent status
            $table->timestamp('curltime')->nullable(); // When sent to POS system
            $table->timestamp('datetime')->useCurrent(); // Order timestamp
            $table->timestamps();

            // Indexes for better performance
            $table->index(['site', 'datetime']);
            $table->index(['paid']);
            $table->index(['ordrestatus']);
            $table->index(['curl']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
