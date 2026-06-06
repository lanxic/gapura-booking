<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('site_settings')->insert([
            // ── Active storage driver ─────────────────────────────────────────
            ['group' => 'storage', 'key' => 'driver', 'value' => 'cloudinary', 'created_at' => $now, 'updated_at' => $now],

            // ── AWS S3 ────────────────────────────────────────────────────────
            ['group' => 'storage_aws', 'key' => 'enabled',                 'value' => 'false',                                   'created_at' => $now, 'updated_at' => $now],
            ['group' => 'storage_aws', 'key' => 'access_key_id',           'value' => env('AWS_ACCESS_KEY_ID', ''),              'created_at' => $now, 'updated_at' => $now],
            ['group' => 'storage_aws', 'key' => 'secret_access_key',       'value' => env('AWS_SECRET_ACCESS_KEY', ''),          'created_at' => $now, 'updated_at' => $now],
            ['group' => 'storage_aws', 'key' => 'region',                  'value' => env('AWS_DEFAULT_REGION', 'ap-southeast-1'), 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'storage_aws', 'key' => 'bucket',                  'value' => env('AWS_BUCKET', ''),                     'created_at' => $now, 'updated_at' => $now],
            ['group' => 'storage_aws', 'key' => 'cdn_url',                 'value' => '',                                        'created_at' => $now, 'updated_at' => $now],
            ['group' => 'storage_aws', 'key' => 'use_path_style_endpoint', 'value' => 'false',                                   'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('site_settings')
            ->whereIn('group', ['storage', 'storage_aws'])
            ->delete();
    }
};
