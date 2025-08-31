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
        Schema::create('pck_credentials', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index(); // References avdeling.siteid
            $table->string('pck_username', 100);
            $table->text('pck_password'); // Encrypted
            $table->string('pck_license', 50);
            $table->string('wsdl_version', 20)->default('1.98');
            $table->json('ip_whitelist')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'pck_username']);
            $table->index(['tenant_id', 'is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pck_credentials');
    }
};