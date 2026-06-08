<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // 'shiprocket', 'delhivery', 'manual'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->text('credentials')->nullable(); // encrypted JSON: API keys, tokens
            $table->json('settings')->nullable();    // provider-specific config
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_providers');
    }
};
