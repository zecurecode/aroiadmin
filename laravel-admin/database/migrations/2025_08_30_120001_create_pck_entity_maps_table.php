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
        Schema::create('pck_entity_maps', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->string('pck_article_id', 100);
            $table->string('pck_variant_id', 100)->nullable();
            $table->bigInteger('woo_product_id')->nullable();
            $table->bigInteger('woo_variation_id')->nullable();
            $table->timestamp('last_timestamp')->nullable();
            $table->string('last_hash', 64)->nullable();
            $table->timestamps();

            // Unique mapping per tenant
            $table->unique(['tenant_id', 'pck_article_id', 'pck_variant_id'], 'pck_entity_unique');
            
            // Indexes for efficient lookups
            $table->index(['tenant_id', 'woo_product_id']);
            $table->index(['tenant_id', 'woo_variation_id']);
            $table->index(['tenant_id', 'last_timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pck_entity_maps');
    }
};