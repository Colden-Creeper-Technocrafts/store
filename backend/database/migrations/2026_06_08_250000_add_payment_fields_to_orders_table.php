<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway', 50)->nullable()->after('payment_status');
            $table->string('payment_id', 100)->nullable()->after('payment_gateway');
            $table->string('razorpay_order_id', 100)->nullable()->after('payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway', 'payment_id', 'razorpay_order_id']);
        });
    }
};
