<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            StoreSettingsSeeder::class,
            CategorySeeder::class,
            BrandsSeeder::class,
            ShippingSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'rgthakkar013@gmail.com'],
            [
                'name'     => 'Raj Thakkar',
                'password' => 'Raj@1234',
            ]
        );

        $admin->syncRoles(['Admin']);
    }
}
