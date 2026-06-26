<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('site_settings')->insert([
            ['group' => 'cloudinary', 'key' => 'cloud_name',     'value' => 'dgausdsp2',              'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'api_key',        'value' => '489248277422639',        'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'api_secret',     'value' => 'b3pqKpEeElrDWzohi1wsQWhNvKo', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'upload_preset',  'value' => '',                       'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'folder_products','value' => 'amartha/products',        'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'folder_tickets', 'value' => 'amartha/tickets',         'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'folder_avatars', 'value' => 'amartha/avatars',         'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'auto_quality',  'value' => 'true',  'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'auto_format',   'value' => 'true',  'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'max_width',     'value' => '1920',  'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'thumb_width',   'value' => '400',   'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('site_settings')->where('group', 'cloudinary')->delete();
    }
};
