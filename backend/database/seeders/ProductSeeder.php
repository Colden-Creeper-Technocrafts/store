<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('product_images')->truncate();
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $stores = DB::table('store_settings')
            ->select(['id', 'layout'])
            ->get()
            ->keyBy('layout');

        $brands = DB::table('brands')->pluck('id', 'name');

        $samples = [
            'grocery' => [
                [
                    'name' => 'Organic Gala Apples',
                    'slug' => 'organic-gala-apples',
                    'sku' => 'GR-APL-001',
                    'short_description' => 'Crisp, sweet apples sourced from organic farms.',
                    'description' => 'Fresh, juicy Gala apples perfect for snacking and baking.',
                    'price' => 4.99,
                    'sale_price' => 3.99,
                    'quantity' => 120,
                    'weight' => 0.35,
                    'category' => 'Tropical Fruits',
                    'brand' => 'Sunrise Organics',
                    'image' => 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=900&q=80',
                ],
                [
                    'name' => 'Almond Milk 1L',
                    'slug' => 'almond-milk-1l',
                    'sku' => 'GR-MLK-002',
                    'short_description' => 'Smooth almond milk for coffee and cereal.',
                    'description' => 'Dairy-free almond milk made from premium nuts with a light finish.',
                    'price' => 3.45,
                    'sale_price' => 2.95,
                    'quantity' => 80,
                    'weight' => 1.0,
                    'category' => 'Milk',
                    'brand' => 'Bloom & Berry',
                    'image' => 'https://images.unsplash.com/photo-1503602642458-232111445657?auto=format&fit=crop&w=900&q=80',
                ],
                [
                    'name' => 'Sourdough Bread',
                    'slug' => 'sourdough-bread',
                    'sku' => 'GR-BRD-003',
                    'short_description' => 'Rustic sourdough with a crisp crust.',
                    'description' => 'Handcrafted sourdough loaf baked fresh daily for a tangy flavor.',
                    'price' => 5.25,
                    'sale_price' => null,
                    'quantity' => 40,
                    'weight' => 0.65,
                    'category' => 'Bread & Buns',
                    'brand' => 'Sunrise Organics',
                    'image' => 'https://images.unsplash.com/photo-1511688878356-97321b7cf77a?auto=format&fit=crop&w=900&q=80',
                ],
                [
                    'name' => 'Cold Brew Coffee',
                    'slug' => 'cold-brew-coffee',
                    'sku' => 'GR-BVG-004',
                    'short_description' => 'Ready-to-drink cold brew for a smooth caffeine boost.',
                    'description' => 'Brewed slow for 18 hours to deliver rich coffee flavor with low acidity.',
                    'price' => 6.2,
                    'sale_price' => 5.5,
                    'quantity' => 50,
                    'weight' => 0.45,
                    'category' => 'Tea & Coffee',
                    'brand' => 'Cedar & Sage',
                    'image' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=900&q=80',
                ],
            ],
            'ladies' => [
                [
                    'name' => 'Pearl Drop Earrings',
                    'slug' => 'pearl-drop-earrings',
                    'sku' => 'LD-ER-001',
                    'short_description' => 'Elegant pearl earrings for special occasions.',
                    'description' => 'Classic drop earrings with lustrous pearl accents and gold details.',
                    'price' => 24.99,
                    'sale_price' => 19.99,
                    'quantity' => 30,
                    'weight' => 0.02,
                    'category' => 'Earrings',
                    'brand' => 'Luna Luxe',
                    'image' => 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&w=900&q=80',
                ],
                [
                    'name' => 'Velvet Lipstick Set',
                    'slug' => 'velvet-lipstick-set',
                    'sku' => 'LD-LP-002',
                    'short_description' => 'Rich shades for day and night looks.',
                    'description' => 'Long-lasting matte lipsticks in a set of three flattering hues.',
                    'price' => 18.5,
                    'sale_price' => 14.99,
                    'quantity' => 55,
                    'weight' => 0.05,
                    'category' => 'Lipsticks',
                    'brand' => 'Velvet Glow',
                    'image' => 'https://images.unsplash.com/photo-1526045612212-70caf35c14df?auto=format&fit=crop&w=900&q=80',
                ],
                [
                    'name' => 'Crystal Necklace Set',
                    'slug' => 'crystal-necklace-set',
                    'sku' => 'LD-NK-003',
                    'short_description' => 'Sparkling set with matching earrings.',
                    'description' => 'Statement necklace with crystal accents, packaged with coordinating earrings.',
                    'price' => 39.9,
                    'sale_price' => 32.9,
                    'quantity' => 20,
                    'weight' => 0.08,
                    'category' => 'Necklace Sets',
                    'brand' => 'Luna Luxe',
                    'image' => 'https://images.unsplash.com/photo-1522312346375-d1a52e2b99b3?auto=format&fit=crop&w=900&q=80',
                ],
                [
                    'name' => 'Aviator Sunglasses',
                    'slug' => 'aviator-sunglasses',
                    'sku' => 'LD-SG-004',
                    'short_description' => 'Classic aviator frames with UV protection.',
                    'description' => 'Lightweight sunglasses with polished metal frames and gradient lenses.',
                    'price' => 29.99,
                    'sale_price' => 24.99,
                    'quantity' => 35,
                    'weight' => 0.1,
                    'category' => 'Sunglasses',
                    'brand' => 'Cedar & Sage',
                    'image' => 'https://images.unsplash.com/photo-1520975698511-9308d5298e7f?auto=format&fit=crop&w=900&q=80',
                ],
            ],
        ];

        foreach ($samples as $layout => $items) {
            $store = $stores[$layout] ?? null;

            if (!$store) {
                continue;
            }

            foreach ($items as $item) {
                $category = DB::table('categories')
                    ->where('store_setting_id', $store->id)
                    ->where('name', $item['category'])
                    ->first();

                if (!$category) {
                    continue;
                }

                $brandId = $brands[$item['brand']] ?? null;

                $productId = DB::table('products')->insertGetId([
                    'name' => $item['name'],
                    'slug' => $item['slug'],
                    'sku' => $item['sku'],
                    'short_description' => $item['short_description'],
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'sale_price' => $item['sale_price'],
                    'quantity' => $item['quantity'],
                    'weight' => $item['weight'],
                    'status' => true,
                    'featured' => false,
                    'brand_id' => $brandId,
                    'category_id' => $category->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('product_images')->insert([
                    'product_id' => $productId,
                    'image' => $item['image'],
                    'sort_order' => 0,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
