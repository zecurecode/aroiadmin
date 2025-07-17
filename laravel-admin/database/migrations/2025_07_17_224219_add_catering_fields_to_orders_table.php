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
            $table->boolean('is_catering')->default(false)->after('site');
            $table->date('delivery_date')->nullable()->after('is_catering');
            $table->time('delivery_time')->nullable()->after('delivery_date');
            $table->text('delivery_address')->nullable()->after('delivery_time');
            $table->integer('number_of_guests')->nullable()->after('delivery_address');
            $table->text('special_requirements')->nullable()->after('number_of_guests');
            $table->text('catering_notes')->nullable()->after('special_requirements');
            $table->string('catering_status')->default('pending')->after('catering_notes');
            $table->string('catering_email')->nullable()->after('catering_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_catering',
                'delivery_date',
                'delivery_time',
                'delivery_address',
                'number_of_guests',
                'special_requirements',
                'catering_notes',
                'catering_status',
                'catering_email'
            ]);
        });
    }
};
