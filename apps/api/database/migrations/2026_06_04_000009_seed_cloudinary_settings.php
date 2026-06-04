<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('site_settings')->insert([
            ['group' => 'cloudinary', 'key' => 'cloud_name',    'value' => env('CLOUDINARY_CLOUD_NAME', ''),           'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'api_key',       'value' => env('CLOUDINARY_API_KEY', ''),              'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'api_secret',    'value' => env('CLOUDINARY_API_SECRET', ''),           'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'upload_preset', 'value' => '',                                         'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'folder_products','value' => env('CLOUDINARY_FOLDER_PRODUCTS', 'amartha/products'), 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'folder_tickets', 'value' => env('CLOUDINARY_FOLDER_TICKETS',  'amartha/tickets'),  'created_at' => $now, 'updated_at' => $now],
            ['group' => 'cloudinary', 'key' => 'folder_avatars', 'value' => env('CLOUDINARY_FOLDER_AVATARS',  'amartha/avatars'),  'created_at' => $now, 'updated_at' => $now],
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
