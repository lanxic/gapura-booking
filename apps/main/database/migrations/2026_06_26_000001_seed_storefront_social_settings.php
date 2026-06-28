<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('system_settings')->insertOrIgnore([
            // ── General additions ─────────────────────────────────────────────
            ['group_name' => 'general', 'key_name' => 'site_address', 'value' => null,  'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],

            // ── Storefront / Hero ──────────────────────────────────────────────
            ['group_name' => 'storefront', 'key_name' => 'hero_image_url', 'value' => null,                                             'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'storefront', 'key_name' => 'hero_title',     'value' => 'Temukan Aktivitas Seru',                         'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'storefront', 'key_name' => 'hero_subtitle',  'value' => 'Pesan tiket aktivitas terbaik dengan mudah dan cepat.', 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'storefront', 'key_name' => 'hero_cta_label', 'value' => 'Jelajahi Aktivitas',                             'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],

            // ── Social Media ──────────────────────────────────────────────────
            ['group_name' => 'social', 'key_name' => 'facebook',  'value' => null, 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'social', 'key_name' => 'instagram', 'value' => null, 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'social', 'key_name' => 'twitter',   'value' => null, 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'social', 'key_name' => 'youtube',   'value' => null, 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'social', 'key_name' => 'whatsapp',  'value' => null, 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'social', 'key_name' => 'tiktok',    'value' => null, 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->where('group_name', 'storefront')
            ->orWhere('group_name', 'social')
            ->orWhere(fn($q) => $q->where('group_name', 'general')->where('key_name', 'site_address'))
            ->delete();
    }
};
