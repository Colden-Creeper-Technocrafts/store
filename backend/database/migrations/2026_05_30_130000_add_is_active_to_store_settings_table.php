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
        if (!Schema::hasTable('store_settings')) {
            return;
        }

        if (!Schema::hasColumn('store_settings', 'is_active')) {
            Schema::table('store_settings', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('layout');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('store_settings')) {
            return;
        }

        if (Schema::hasColumn('store_settings', 'is_active')) {
            Schema::table('store_settings', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
