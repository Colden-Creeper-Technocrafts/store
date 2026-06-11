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
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->string('phone', 30)->index()->after('id');
            $table->string('email')->nullable()->after('phone');
            $table->string('otp', 6)->after('email');
            $table->string('email_token', 64)->nullable()->unique()->after('otp');
            $table->unsignedBigInteger('order_id')->nullable()->after('email_token');
            $table->timestamp('expires_at')->after('order_id');
            $table->timestamp('verified_at')->nullable()->after('expires_at');
            $table->timestamp('email_verified_at')->nullable()->after('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email', 'otp', 'email_token', 'order_id', 'expires_at', 'verified_at', 'email_verified_at']);
        });
    }
};
