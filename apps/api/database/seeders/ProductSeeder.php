<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // ── Produk 1: Safari Legend (Selasa–Minggu) ──────────────────────────
        $safariLegend = DB::table('products')->insertGetId([
            'name'        => 'Safari Legend',
            'slug'        => 'safari-legend-selasa-minggu',
            'description' => 'Rasakan pengalaman safari siang hari yang menakjubkan bersama ratusan satwa liar. Lihat harimau, gajah, jerapah, dan banyak lagi dalam habitat alaminya.',
            'is_active'   => true,
            'sort_order'  => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $variantWeekday = DB::table('product_variants')->insertGetId([
            'product_id'   => $safariLegend,
            'label'        => 'Weekday (Selasa–Jumat)',
            'price_adult'  => 185000,
            'price_child'  => 130000,
            'min_qty'      => 1,
            'max_qty'      => 50,
            'is_active'    => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $variantWeekend = DB::table('product_variants')->insertGetId([
            'product_id'   => $safariLegend,
            'label'        => 'Weekend (Sabtu–Minggu)',
            'price_adult'  => 225000,
            'price_child'  => 165000,
            'min_qty'      => 1,
            'max_qty'      => 50,
            'is_active'    => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // ── Produk 2: Safari Malam ────────────────────────────────────────────
        $safariMalam = DB::table('products')->insertGetId([
            'name'        => 'Safari Malam',
            'slug'        => 'safari-malam-premium',
            'description' => 'Jelajahi dunia satwa di malam hari. Saksikan hewan nokturnal beraksi dalam kegelapan dengan pencahayaan khusus yang dramatis.',
            'is_active'   => true,
            'sort_order'  => 2,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $variantMalamRegular = DB::table('product_variants')->insertGetId([
            'product_id'   => $safariMalam,
            'label'        => 'Regular',
            'price_adult'  => 250000,
            'price_child'  => 180000,
            'min_qty'      => 1,
            'max_qty'      => 40,
            'is_active'    => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $variantMalamVIP = DB::table('product_variants')->insertGetId([
            'product_id'   => $safariMalam,
            'label'        => 'VIP (Termasuk Dinner)',
            'price_adult'  => 450000,
            'price_child'  => 350000,
            'min_qty'      => 2,
            'max_qty'      => 20,
            'is_active'    => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // ── Add-ons ───────────────────────────────────────────────────────────
        $addonPhoto = DB::table('addons')->insertGetId([
            'name'        => 'Sesi Foto Profesional',
            'description' => 'Sesi foto 30 menit dengan fotografer profesional + 5 foto digital',
            'price'       => 150000,
            'max_qty'     => 5,
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $addonBus = DB::table('addons')->insertGetId([
            'name'        => 'Antar-Jemput Bus Wisata',
            'description' => 'Layanan bus dari titik kumpul ke venue PP',
            'price'       => 75000,
            'max_qty'     => 10,
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $addonMeal = DB::table('addons')->insertGetId([
            'name'        => 'Makan Siang Prasmanan',
            'description' => 'Buffet makan siang di restoran venue (menu Indonesia)',
            'price'       => 120000,
            'max_qty'     => 10,
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Hubungkan add-ons ke produk
        DB::table('product_addon')->insert([
            ['product_id' => $safariLegend, 'addon_id' => $addonPhoto, 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $safariLegend, 'addon_id' => $addonBus,   'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $safariLegend, 'addon_id' => $addonMeal,  'is_active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $safariMalam,  'addon_id' => $addonPhoto, 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $safariMalam,  'addon_id' => $addonBus,   'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Availability Slots (30 hari ke depan) ─────────────────────────────
        $slots = [];
        for ($i = 0; $i <= 30; $i++) {
            $date    = now()->addDays($i)->toDateString();
            $isBlocked = $i === 10; // blokir 1 hari sebagai contoh

            // Slot untuk Safari Legend
            $slots[] = [
                'product_id'  => $safariLegend,
                'date'        => $date,
                'time_slot'   => '08:00-17:00',
                'total_quota' => 100,
                'booked_qty'  => 0,
                'is_blocked'  => $isBlocked,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];

            // Slot untuk Safari Malam
            $slots[] = [
                'product_id'  => $safariMalam,
                'date'        => $date,
                'time_slot'   => '18:00-22:00',
                'total_quota' => 60,
                'booked_qty'  => 0,
                'is_blocked'  => $isBlocked,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }
        DB::table('availability_slots')->insert($slots);

        $this->command->info('  Products, variants, addons, and 62 availability slots seeded.');
    }
}
