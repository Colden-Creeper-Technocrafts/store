<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For every product that has variants but none marked as default,
        // mark the lowest-id variant as default and sync product price/quantity from it.
        $productIds = DB::table('products')
            ->whereExists(fn($q) => $q->select(DB::raw(1))
                ->from('product_variants')
                ->whereColumn('product_variants.product_id', 'products.id'))
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                ->from('product_variants')
                ->whereColumn('product_variants.product_id', 'products.id')
                ->where('product_variants.is_default', true))
            ->pluck('id');

        foreach ($productIds as $productId) {
            $first = DB::table('product_variants')
                ->where('product_id', $productId)
                ->orderBy('id')
                ->first();

            if (!$first) continue;

            DB::table('product_variants')->where('id', $first->id)->update(['is_default' => true]);
            DB::table('products')->where('id', $productId)->update([
                'price'    => $first->price,
                'quantity' => $first->quantity,
            ]);
        }
    }

    public function down(): void {}
};
