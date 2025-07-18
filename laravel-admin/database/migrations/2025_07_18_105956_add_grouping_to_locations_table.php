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
        Schema::table('locations', function (Blueprint $table) {
            $table->string('group_name')->nullable()->after('name');
            $table->integer('display_order')->default(0)->after('active');
            $table->index('group_name');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['group_name']);
            $table->dropIndex(['display_order']);
            $table->dropColumn('group_name');
            $table->dropColumn('display_order');
        });
    }
};
