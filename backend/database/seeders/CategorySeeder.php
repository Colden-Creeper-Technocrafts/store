<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $stores = DB::table('store_settings')
            ->select(['id', 'layout'])
            ->get()
            ->keyBy('layout');

        $catalog = $this->catalog();

        foreach ($catalog as $layout => $categories) {
            $storeId = $stores[$layout]->id ?? null;

            if (!$storeId) {
                continue;
            }

            $this->seedTree($storeId, $categories);
        }
    }

    private function seedTree(int $storeId, array $nodes, ?int $parentId = null, string $slugPrefix = ''): void
    {
        foreach ($nodes as $index => $node) {
            $slug = $this->buildSlug($slugPrefix, $node['name']);

            $categoryId = DB::table('categories')->insertGetId([
                'store_setting_id' => $storeId,
                'parent_category_id' => $parentId,
                'name' => $node['name'],
                'slug' => $slug,
                'description' => $node['description'] ?? null,
                'sort_order' => $index + 1,
                'is_active' => $node['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!empty($node['children'])) {
                $this->seedTree($storeId, $node['children'], $categoryId, $slug);
            }
        }
    }

    private function buildSlug(string $prefix, string $name): string
    {
        $slug = Str::slug($name);

        if ($prefix === '') {
            return $slug;
        }

        return "{$prefix}-{$slug}";
    }

    private function catalog(): array
    {
        return [
            'grocery' => [
                [
                    'name' => 'Fruits & Vegetables',
                    'children' => [
                        [
                            'name' => 'Fresh Vegetables',
                            'children' => [
                                ['name' => 'Leafy Greens'],
                                ['name' => 'Root Vegetables'],
                                ['name' => 'Exotic Vegetables'],
                                ['name' => 'Gourds & Cucumbers'],
                            ],
                        ],
                        [
                            'name' => 'Fresh Fruits',
                            'children' => [
                                ['name' => 'Tropical Fruits'],
                                ['name' => 'Citrus Fruits'],
                                ['name' => 'Apples & Pears'],
                                ['name' => 'Berries & Cherries'],
                            ],
                        ],
                        ['name' => 'Herbs & Seasonings'],
                        ['name' => 'Sprouts & Microgreens'],
                        ['name' => 'Cut & Peeled'],
                    ],
                ],
                [
                    'name' => 'Dairy & Bread',
                    'children' => [
                        ['name' => 'Milk'],
                        ['name' => 'Curd & Yogurt'],
                        ['name' => 'Paneer & Cheese'],
                        ['name' => 'Butter & Ghee'],
                        ['name' => 'Bread & Buns'],
                    ],
                ],
                [
                    'name' => 'Rice, Atta & Pulses',
                    'children' => [
                        ['name' => 'Rice'],
                        ['name' => 'Atta & Flour'],
                        ['name' => 'Dals & Pulses'],
                        ['name' => 'Millets'],
                        ['name' => 'Poha, Dalia & Sooji'],
                    ],
                ],
                [
                    'name' => 'Oils & Masalas',
                    'children' => [
                        ['name' => 'Edible Oils'],
                        ['name' => 'Basic Spices'],
                        ['name' => 'Whole Spices'],
                        ['name' => 'Ready Masala Mixes'],
                        ['name' => 'Salt, Sugar & Jaggery'],
                    ],
                ],
                [
                    'name' => 'Breakfast & Instant Food',
                    'children' => [
                        ['name' => 'Breakfast Cereals'],
                        ['name' => 'Oats & Muesli'],
                        ['name' => 'Noodles & Pasta'],
                        ['name' => 'Instant Mixes'],
                        ['name' => 'Peanut Butter & Spreads'],
                    ],
                ],
                [
                    'name' => 'Snacks & Beverages',
                    'children' => [
                        ['name' => 'Chips & Namkeen'],
                        ['name' => 'Biscuits & Cookies'],
                        ['name' => 'Chocolates & Candy'],
                        ['name' => 'Cold Drinks & Juices'],
                        ['name' => 'Tea & Coffee'],
                        ['name' => 'Health Drinks'],
                    ],
                ],
                [
                    'name' => 'Frozen & Ready to Cook',
                    'children' => [
                        ['name' => 'Frozen Veg Snacks'],
                        ['name' => 'Plant-Based Frozen Meals'],
                        ['name' => 'Ready to Cook Meals'],
                        ['name' => 'Frozen Breads'],
                        ['name' => 'Ice Cream & Desserts'],
                    ],
                ],
                [
                    'name' => 'Baby Care',
                    'children' => [
                        ['name' => 'Baby Food'],
                        ['name' => 'Diapers & Wipes'],
                        ['name' => 'Baby Bath & Skin Care'],
                        ['name' => 'Baby Health & Hygiene'],
                    ],
                ],
                [
                    'name' => 'Personal Care',
                    'children' => [
                        ['name' => 'Skin Care'],
                        ['name' => 'Hair Care'],
                        ['name' => 'Oral Care'],
                        ['name' => 'Bath & Body'],
                        ['name' => 'Feminine Hygiene'],
                        ['name' => 'Men Grooming'],
                    ],
                ],
                [
                    'name' => 'Home Care & Cleaning',
                    'children' => [
                        ['name' => 'Detergents'],
                        ['name' => 'Dishwash'],
                        ['name' => 'Floor & Surface Cleaners'],
                        ['name' => 'Toilet Cleaners'],
                        ['name' => 'Air Fresheners'],
                        ['name' => 'Insect Repellents'],
                    ],
                ],
                [
                    'name' => 'Kitchen & Household',
                    'children' => [
                        ['name' => 'Foils & Wraps'],
                        ['name' => 'Storage Containers'],
                        ['name' => 'Disposables'],
                        ['name' => 'Mops, Brushes & Gloves'],
                        ['name' => 'Batteries & Utilities'],
                    ],
                ],
                [
                    'name' => 'Pet Care',
                    'children' => [
                        ['name' => 'Dog Food'],
                        ['name' => 'Cat Food'],
                        ['name' => 'Pet Treats'],
                        ['name' => 'Pet Hygiene'],
                    ],
                ],
            ],
            'ladies' => [
                [
                    'name' => 'Jewelry & Bangles',
                    'children' => [
                        ['name' => 'Glass Bangles'],
                        ['name' => 'Metal Bangles'],
                        ['name' => 'Bracelets'],
                        ['name' => 'Necklace Sets'],
                        ['name' => 'Earrings'],
                        ['name' => 'Anklets'],
                        ['name' => 'Rings'],
                    ],
                ],
                [
                    'name' => 'Hair Accessories',
                    'children' => [
                        ['name' => 'Scrunchies'],
                        ['name' => 'Hair Pins'],
                        ['name' => 'Hair Bands'],
                        ['name' => 'Hair Clutches'],
                        ['name' => 'Juda Pins'],
                        ['name' => 'Rubber Bands'],
                    ],
                ],
                [
                    'name' => 'Clutches & Wallets',
                    'children' => [
                        ['name' => 'Party Clutches'],
                        ['name' => 'Casual Clutches'],
                        ['name' => 'Ethnic Potlis'],
                        ['name' => 'Wallets'],
                        ['name' => 'Card Holders'],
                    ],
                ],
                [
                    'name' => 'Cosmetics & Beauty',
                    'children' => [
                        ['name' => 'Face Makeup'],
                        ['name' => 'Eye Makeup'],
                        ['name' => 'Lipsticks'],
                        ['name' => 'Nail Care'],
                        ['name' => 'Skin Care'],
                        ['name' => 'Fragrances'],
                        ['name' => 'Beauty Tools'],
                    ],
                ],
                [
                    'name' => 'Kids Accessories',
                    'children' => [
                        ['name' => 'Girls Hair Accessories'],
                        ['name' => 'Kids Jewelry'],
                        ['name' => 'School Accessories'],
                        ['name' => 'Soft Toys'],
                        ['name' => 'Kids Fashion Bags'],
                    ],
                ],
                [
                    'name' => 'Gift Articles',
                    'children' => [
                        ['name' => 'Gift Sets'],
                        ['name' => 'Return Gifts'],
                        ['name' => 'Festival Gifts'],
                        ['name' => 'Home Decor Gifts'],
                        ['name' => 'Personalized Gifts'],
                    ],
                ],
                [
                    'name' => 'Seasonal & Festive',
                    'children' => [
                        ['name' => 'Wedding Favors'],
                        ['name' => 'Party Accessories'],
                        ['name' => 'Rakhi Collection'],
                        ['name' => 'Diwali Decor'],
                        ['name' => 'Navratri Specials'],
                    ],
                ],
                [
                    'name' => 'Fashion Accessories',
                    'children' => [
                        ['name' => 'Sunglasses'],
                        ['name' => 'Belts'],
                        ['name' => 'Scarves & Stoles'],
                        ['name' => 'Watches'],
                        ['name' => 'Keychains'],
                    ],
                ],
            ],
        ];
    }
}
