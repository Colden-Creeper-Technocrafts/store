<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $stores = [
            [
                'id' => 1,
                'store_name' => 'Kumkum Novelty Store',
                'layout' => 'ladies',
                'is_active' => false,
            ],
            [
                'id' => 2,
                'store_name' => 'Hariom Provision store',
                'layout' => 'grocery',
                'is_active' => true,
            ],
        ];

        $activeCount = collect($stores)->where('is_active', true)->count();
        if ($activeCount !== 1) {
            throw new \RuntimeException('StoreSettingsSeeder requires exactly one active store.');
        }

        DB::table('store_settings')->delete();

        DB::table('store_settings')->insert(
            array_map(static fn (array $store) => [
                ...$store,
                'created_at' => $now,
                'updated_at' => $now,
            ], $stores)
        );
    }
}
