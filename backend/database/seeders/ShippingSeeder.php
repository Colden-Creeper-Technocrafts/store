<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedProviders();
        $this->seedZones();
        $this->seedMethodsAndRates();
    }

    // ── Providers ─────────────────────────────────────────────────────────────

    private function seedProviders(): void
    {
        $providers = [
            [
                'name'        => 'Manual',
                'slug'        => 'manual',
                'description' => 'Manual shipping — AWB and tracking entered by admin.',
                'is_active'   => true,
                'credentials' => null,
                'settings'    => [
                    'flat_rate'   => 50,
                    'method_name' => 'Standard Shipping',
                    'min_days'    => 3,
                    'max_days'    => 7,
                ],
                'sort_order'  => 10,
            ],
            [
                'name'        => 'Shiprocket',
                'slug'        => 'shiprocket',
                'description' => 'Shiprocket multi-carrier aggregator.',
                'is_active'   => false,
                'credentials' => null, // set email + password via admin
                'settings'    => [
                    'pickup_location' => 'Primary',
                    'pickup_pincode'  => '',
                    'default_length'  => 10,
                    'default_width'   => 10,
                    'default_height'  => 10,
                ],
                'sort_order'  => 20,
            ],
            [
                'name'        => 'Delhivery',
                'slug'        => 'delhivery',
                'description' => 'Delhivery express and surface shipping.',
                'is_active'   => false,
                'credentials' => null, // set api_token via admin
                'settings'    => [
                    'pickup_name'    => 'Primary',
                    'pickup_pincode' => '',
                    'pickup_address' => '',
                    'seller_name'    => '',
                    'shipping_mode'  => 'Surface',
                    'default_length' => 10,
                    'default_width'  => 10,
                    'default_height' => 10,
                    'min_days'       => 4,
                    'max_days'       => 7,
                    'use_staging'    => false,
                ],
                'sort_order'  => 30,
            ],
        ];

        foreach ($providers as $data) {
            ShippingProvider::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }

    // ── Zones ─────────────────────────────────────────────────────────────────

    private function seedZones(): void
    {
        // Metro Cities zone — higher rates / faster delivery
        $metro = ShippingZone::firstOrCreate(
            ['name' => 'Metro Cities'],
            ['description' => 'Delhi, Mumbai, Bangalore, Chennai, Hyderabad, Kolkata, Pune, Ahmedabad', 'is_active' => true, 'sort_order' => 1]
        );

        $metroPrefixes = [
            '110', '111', '112',        // Delhi NCR
            '400', '401', '402', '421', // Mumbai
            '560', '561', '562',        // Bangalore
            '600', '601', '602',        // Chennai
            '500', '501', '502',        // Hyderabad
            '700', '711',               // Kolkata
            '411', '412',               // Pune
            '380', '382',               // Ahmedabad
        ];

        foreach ($metroPrefixes as $prefix) {
            ShippingZoneLocation::firstOrCreate([
                'shipping_zone_id' => $metro->id,
                'type'             => 'pincode_prefix',
                'value'            => $prefix,
            ]);
        }

        // All India zone — catch-all
        $allIndia = ShippingZone::firstOrCreate(
            ['name' => 'All India'],
            ['description' => 'Rest of India — covers all pincodes not in another zone.', 'is_active' => true, 'sort_order' => 99]
        );

        // No locations needed: catch-all rates use zone_id = null on ShippingRate rows.
        // The zone record exists so admins can explicitly assign pincodes to it via the dashboard.
    }

    // ── Methods & Rates ───────────────────────────────────────────────────────

    private function seedMethodsAndRates(): void
    {
        $manual = ShippingProvider::where('slug', 'manual')->first();
        if (!$manual) return;

        $metro    = ShippingZone::where('name', 'Metro Cities')->first();
        $metroId  = $metro?->id;

        // Standard Shipping method
        $standard = ShippingMethod::firstOrCreate(
            ['shipping_provider_id' => $manual->id, 'code' => 'standard'],
            [
                'name'        => 'Standard Shipping',
                'description' => 'Delivered in 3–7 business days.',
                'min_days'    => 3,
                'max_days'    => 7,
                'is_active'   => true,
                'sort_order'  => 1,
            ]
        );

        // Rate: Metro — ₹40 flat + ₹15/kg above 0.5 kg
        if ($metroId) {
            ShippingRate::firstOrCreate(
                ['shipping_method_id' => $standard->id, 'shipping_zone_id' => $metroId, 'min_weight_kg' => 0],
                [
                    'max_weight_kg'    => null,
                    'min_order_amount' => null,
                    'max_order_amount' => null,
                    'base_rate'        => 40.00,
                    'per_kg_rate'      => 15.00,
                    'is_free'          => false,
                    'sort_order'       => 1,
                ]
            );
        }

        // Rate: All India catch-all — ₹60 flat + ₹20/kg
        ShippingRate::firstOrCreate(
            ['shipping_method_id' => $standard->id, 'shipping_zone_id' => null, 'min_weight_kg' => 0],
            [
                'max_weight_kg'    => null,
                'min_order_amount' => null,
                'max_order_amount' => null,
                'base_rate'        => 60.00,
                'per_kg_rate'      => 20.00,
                'is_free'          => false,
                'sort_order'       => 99,
            ]
        );

        // Free shipping rule: orders ≥ ₹999, catch-all
        ShippingRate::firstOrCreate(
            ['shipping_method_id' => $standard->id, 'shipping_zone_id' => null, 'min_weight_kg' => 0, 'min_order_amount' => 999],
            [
                'max_weight_kg'    => null,
                'min_order_amount' => 999.00,
                'max_order_amount' => null,
                'base_rate'        => 0.00,
                'per_kg_rate'      => 0.00,
                'is_free'          => true,
                'sort_order'       => 0, // checked first — overrides the paid rate
            ]
        );

        $this->command->info('Shipping providers, zones, methods, and rates seeded.');
    }
}
