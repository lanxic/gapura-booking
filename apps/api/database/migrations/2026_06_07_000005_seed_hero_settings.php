<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('site_settings')->insert([
            ['group' => 'hero', 'key' => 'title',           'value' => 'Temukan Pengalaman Wisata Terbaik',                        'created_at' => $now, 'updated_at' => $now],
            ['group' => 'hero', 'key' => 'subtitle',        'value' => 'Pesan tiket Taman Safari Bali secara online dengan mudah dan aman', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'hero', 'key' => 'image_url',       'value' => '',                                                        'created_at' => $now, 'updated_at' => $now],
            ['group' => 'hero', 'key' => 'image_id',        'value' => '',                                                        'created_at' => $now, 'updated_at' => $now],
            ['group' => 'hero', 'key' => 'cta_label',       'value' => 'Pesan Tiket Sekarang',                                    'created_at' => $now, 'updated_at' => $now],
            ['group' => 'hero', 'key' => 'cta_url',         'value' => '#produk',                                                 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'hero', 'key' => 'overlay_color',   'value' => '#000000',                                                 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'hero', 'key' => 'overlay_opacity', 'value' => '0.45',                                                    'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('site_settings')->where('group', 'hero')->delete();
    }
};
