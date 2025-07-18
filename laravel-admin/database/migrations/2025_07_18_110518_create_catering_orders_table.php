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
        Schema::create('catering_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->integer('site_id');
            $table->string('order_number')->unique();
            
            // Delivery information
            $table->date('delivery_date');
            $table->string('delivery_time');
            $table->text('delivery_address');
            $table->integer('number_of_guests');
            
            // Contact information
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->string('contact_email');
            
            // Invoice information
            $table->string('company_name');
            $table->string('company_org_number');
            $table->text('invoice_address');
            $table->string('invoice_email');
            
            // Order details
            $table->text('special_requirements')->nullable();
            $table->text('catering_notes')->nullable();
            $table->json('products');
            $table->decimal('total_amount', 10, 2);
            
            // Status tracking
            $table->string('status')->default('pending');
            $table->string('catering_email')->nullable();
            $table->timestamp('invoice_sent_at')->nullable();
            $table->timestamp('invoice_paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['site_id', 'delivery_date']);
            $table->index('status');
            $table->index('delivery_date');
            
            // Foreign key
            $table->foreign('location_id')->references('id')->on('locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catering_orders');
    }
};
