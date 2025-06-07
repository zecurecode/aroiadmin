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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Site name (Namsos, Lade, etc.)
            $table->integer('site_id')->unique(); // WordPress site ID (4, 5, 6, 7, 10, 11)
            $table->string('url'); // WooCommerce site URL
            $table->string('consumer_key'); // WooCommerce API consumer key
            $table->string('consumer_secret'); // WooCommerce API consumer secret
            $table->integer('license')->default(0); // PCKasse license number
            $table->boolean('active')->default(true); // Site status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
