<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductSchedule;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenantAdventure = Tenant::where('slug', 'adventure')->firstOrFail();

        $products = [
            [
                'tenant_id'        => $tenantAdventure->id,
                'name'             => 'Hiking Gunung Bunder',
                'category'         => 'outdoor',
                'type'             => 'aktivitas',
                'description'      => 'Petualangan hiking ke Gunung Bunder dengan pemandu lokal berpengalaman. Menyaksikan sunrise yang memukau.',
                'duration_minutes' => 360,
                'min_pax'          => 4,
                'max_pax'          => 20,
                'level'            => 'intermediate',
                'min_age'          => 15,
                'price_adult'      => 275000,
                'status'           => 'active',
                'is_featured'      => true,
                'meta'             => ['meeting_point' => 'Basecamp Gunung Bunder, Bogor'],
                'schedules'        => [
                    ['day_of_week' => 5, 'start_time' => '03:00', 'end_time' => '09:00', 'default_capacity' => 20],
                    ['day_of_week' => 6, 'start_time' => '03:00', 'end_time' => '09:00', 'default_capacity' => 20],
                ],
                'addons' => [
                    ['name' => 'Trekking Pole Rental', 'price' => 35000],
                    ['name' => 'Porter Service',       'price' => 150000],
                ],
            ],
            [
                'tenant_id'        => $tenantAdventure->id,
                'name'             => 'Stand-Up Paddleboarding (SUP)',
                'category'         => 'outdoor',
                'type'             => 'aktivitas',
                'description'      => 'Belajar SUP di danau yang tenang. Instruktur bersertifikat dan peralatan lengkap.',
                'duration_minutes' => 90,
                'min_pax'          => 2,
                'max_pax'          => 8,
                'level'            => 'beginner',
                'min_age'          => 12,
                'price_adult'      => 325000,
                'status'           => 'active',
                'is_featured'      => true,
                'meta'             => ['equipment_included' => true, 'location' => 'Danau Situ Gunung, Sukabumi'],
                'schedules'        => [
                    ['day_of_week' => 6, 'start_time' => '08:00', 'end_time' => '09:30', 'default_capacity' => 8],
                    ['day_of_week' => 0, 'start_time' => '08:00', 'end_time' => '09:30', 'default_capacity' => 8],
                ],
                'addons' => [
                    ['name' => 'Underwater Photo Package', 'price' => 100000],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $schedules = $productData['schedules'];
            $addons    = $productData['addons'];
            unset($productData['schedules'], $productData['addons']);

            $slug    = Str::slug($productData['name']);
            $product = Product::firstOrCreate(
                ['slug' => $slug],
                array_merge($productData, ['slug' => $slug])
            );

            foreach ($schedules as $schedule) {
                ProductSchedule::firstOrCreate(
                    ['product_id' => $product->id, 'day_of_week' => $schedule['day_of_week'], 'start_time' => $schedule['start_time']],
                    array_merge($schedule, ['product_id' => $product->id, 'is_active' => true])
                );
            }

            foreach ($addons as $addon) {
                ProductAddon::firstOrCreate(
                    ['product_id' => $product->id, 'name' => $addon['name']],
                    ['price' => $addon['price'], 'unit' => 'pax', 'max_qty' => 1, 'is_active' => true]
                );
            }
        }

        $this->command->info('  Products seeded: ' . count($products) . ' produk (Adventure Hub).');
    }
}
