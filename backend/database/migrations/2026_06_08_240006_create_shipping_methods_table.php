<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_provider_id')->constrained()->cascadeOnDelete();
            $table->string('name');              // 'Standard Delivery', 'Express Delivery'
            $table->string('code', 50);          // 'standard', 'express', 'same_day'
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('min_days')->default(1);
            $table->unsignedSmallInteger('max_days')->default(7);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['shipping_provider_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
