<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Dedicated table untuk multi-provider media storage.
// Hanya 1 row is_active=true. Admin switch dari Admin Portal → Settings → Storage.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // cloudinary | cloudflare_r2 | aws_s3
            $table->boolean('is_active')->default(false);

            // Konfigurasi terenkripsi sebagai JSON
            // cloudinary: {cloud_name, api_key, api_secret, folder_prefix}
            // cloudflare_r2: {account_id, access_key_id, secret_access_key, bucket, endpoint, cdn_base_url}
            // aws_s3: {access_key_id, secret_access_key, region, bucket, cdn_base_url}
            $table->json('config')->nullable(); // enc

            $table->unsignedBigInteger('max_file_size')->default(10485760); // 10 MB
            $table->json('allowed_formats')->nullable(); // ["jpg","png","webp","pdf"]
            $table->string('folder_prefix', 100)->nullable(); // prefix folder dalam bucket/cloud

            $table->timestamps();
        });

        $now = now();
        DB::table('storage_providers')->insert([
            [
                'name'            => 'cloudinary',
                'is_active'       => true,
                'config'          => null,
                'max_file_size'   => 10485760,
                'allowed_formats' => json_encode(['jpg', 'jpeg', 'png', 'webp', 'pdf']),
                'folder_prefix'   => 'activity-booking',
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'name'            => 'cloudflare_r2',
                'is_active'       => false,
                'config'          => null,
                'max_file_size'   => 10485760,
                'allowed_formats' => json_encode(['jpg', 'jpeg', 'png', 'webp', 'pdf']),
                'folder_prefix'   => 'activity-booking',
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'name'            => 'aws_s3',
                'is_active'       => false,
                'config'          => null,
                'max_file_size'   => 10485760,
                'allowed_formats' => json_encode(['jpg', 'jpeg', 'png', 'webp', 'pdf']),
                'folder_prefix'   => 'activity-booking',
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_providers');
    }
};
