<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Dedicated table menggantikan group gateway_* di site_settings.
// Constraint: hanya 1 row is_active=true (enforced via DB trigger / application layer).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // midtrans | doku
            $table->boolean('is_active')->default(false);

            // Credentials terenkripsi AES-256 via Laravel Encryption
            $table->text('merchant_id')->nullable();   // enc
            $table->text('server_key')->nullable();    // enc
            $table->text('client_key')->nullable();    // enc
            $table->string('environment', 20)->default('sandbox'); // sandbox | production

            // Konfigurasi tambahan (snap_url, timeout, dll) sebagai JSON enc
            $table->json('config')->nullable();

            $table->timestamps();
        });

        // Seed default gateways
        $now = now();
        DB::table('payment_gateways')->insert([
            [
                'name'        => 'midtrans',
                'is_active'   => true, // default aktif
                'environment' => 'sandbox',
                'config'      => json_encode(['snap_url' => 'https://app.sandbox.midtrans.com/snap/snap.js']),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'doku',
                'is_active'   => false,
                'environment' => 'sandbox',
                'config'      => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
