<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();
            // null zone_id = applies to all zones (catch-all)
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('min_weight_kg', 8, 3)->default(0);
            $table->decimal('max_weight_kg', 8, 3)->nullable(); // null = no upper limit
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_order_amount', 10, 2)->nullable();
            $table->decimal('base_rate', 10, 2)->default(0);
            $table->decimal('per_kg_rate', 10, 2)->default(0); // charged per kg above min
            $table->boolean('is_free')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0); // lower = higher priority
            $table->timestamps();

            $table->index(['shipping_method_id', 'shipping_zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
