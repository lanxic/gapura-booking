<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('site_settings')->insert([
            // ── Midtrans ──────────────────────────────────────────────────────
            ['group' => 'gateway_midtrans', 'key' => 'enabled',     'value' => 'true',  'created_at' => $now, 'updated_at' => $now],
            ['group' => 'gateway_midtrans', 'key' => 'environment',  'value' => env('MIDTRANS_IS_PRODUCTION', 'false') === 'true' ? 'production' : 'sandbox', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'gateway_midtrans', 'key' => 'server_key',   'value' => env('MIDTRANS_SERVER_KEY', ''),   'created_at' => $now, 'updated_at' => $now],
            ['group' => 'gateway_midtrans', 'key' => 'client_key',   'value' => env('MIDTRANS_CLIENT_KEY', ''),   'created_at' => $now, 'updated_at' => $now],
            ['group' => 'gateway_midtrans', 'key' => 'snap_url',     'value' => 'https://app.sandbox.midtrans.com/snap/snap.js', 'created_at' => $now, 'updated_at' => $now],

            // ── Doku ─────────────────────────────────────────────────────────
            ['group' => 'gateway_doku', 'key' => 'enabled',     'value' => 'false', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'gateway_doku', 'key' => 'environment', 'value' => 'sandbox', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'gateway_doku', 'key' => 'mall_id',     'value' => '',       'created_at' => $now, 'updated_at' => $now],
            ['group' => 'gateway_doku', 'key' => 'secret_key',  'value' => '',       'created_at' => $now, 'updated_at' => $now],
            ['group' => 'gateway_doku', 'key' => 'client_id',   'value' => '',       'created_at' => $now, 'updated_at' => $now],

            // ── Cash ─────────────────────────────────────────────────────────
            ['group' => 'gateway_cash', 'key' => 'enabled', 'value' => 'true', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Hapus kolom gateway dari group payment (dipindah ke gateway_* groups)
        DB::table('site_settings')
            ->where('group', 'payment')
            ->whereIn('key', ['midtrans_enabled', 'cash_enabled'])
            ->delete();
    }

    public function down(): void
    {
        DB::table('site_settings')->whereIn('group', ['gateway_midtrans', 'gateway_doku', 'gateway_cash'])->delete();
    }
};
