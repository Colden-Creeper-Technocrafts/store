<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('business_type')->nullable()->after('store_name');
            $table->string('store_email')->nullable()->after('business_type');
            $table->string('store_phone')->nullable()->after('store_email');
            $table->text('store_description')->nullable()->after('store_phone');
            $table->string('currency', 3)->default('INR')->after('store_description');
            $table->json('features')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['business_type', 'store_email', 'store_phone', 'store_description', 'currency', 'features']);
        });
    }
};
