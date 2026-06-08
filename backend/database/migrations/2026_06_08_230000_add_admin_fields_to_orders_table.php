<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('pending')->after('status');
            $table->text('admin_notes')->nullable()->after('notes');
            $table->string('tracking_number', 100)->nullable()->after('admin_notes');
            $table->string('tracking_url', 500)->nullable()->after('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'admin_notes', 'tracking_number', 'tracking_url']);
        });
    }
};
