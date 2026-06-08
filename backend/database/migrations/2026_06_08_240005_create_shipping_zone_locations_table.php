<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zone_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            // type: 'state' | 'pincode_prefix' (first 3 digits) | 'pincode' (exact)
            $table->string('type', 20);
            $table->string('value', 20); // state name, '110', or '110001'
            $table->timestamps();

            $table->index(['shipping_zone_id', 'type']);
            $table->index(['type', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zone_locations');
    }
};
