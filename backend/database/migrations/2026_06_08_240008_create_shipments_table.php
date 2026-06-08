<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_provider_id')->constrained();
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('awb_number')->nullable()->unique();          // Air Waybill / tracking no.
            $table->string('tracking_url')->nullable();
            $table->string('provider_shipment_id')->nullable();         // ID in provider's system
            $table->string('label_url')->nullable();                    // shipping label PDF
            $table->string('status', 30)->default('pending');
            // statuses: pending, booked, ready_to_ship, picked_up, in_transit,
            //           out_for_delivery, delivered, failed, cancelled, rto_initiated, rto_delivered
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('meta')->nullable(); // provider-specific response data
            $table->timestamps();

            $table->index('status');
            $table->index('awb_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
