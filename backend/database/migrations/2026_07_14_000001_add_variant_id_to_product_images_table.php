<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
        });

        // Assign existing images to the default variant of their product
        $productIds = DB::table('product_images')
            ->whereNull('product_variant_id')
            ->distinct()
            ->pluck('product_id');

        foreach ($productIds as $productId) {
            $variantId = DB::table('product_variants')
                ->where('product_id', $productId)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->value('id');

            if ($variantId) {
                DB::table('product_images')
                    ->where('product_id', $productId)
                    ->whereNull('product_variant_id')
                    ->update(['product_variant_id' => $variantId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
        });
    }
};
