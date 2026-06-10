<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('banner_image')->nullable()->after('logo_url');
            $table->string('banner_title')->nullable()->after('banner_image');
            $table->text('banner_text')->nullable()->after('banner_title');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['banner_image', 'banner_title', 'banner_text']);
        });
    }
};
