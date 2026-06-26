<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `type` ke payment_gateways untuk membedakan:
 * - online  : Midtrans, DOKU (butuh server_key/client_key)
 * - offline : Cash/Tunai, Transfer Bank (tidak butuh credentials)
 *
 * Juga tambah kolom `notes` untuk instruksi pembayaran offline (nomor rekening, dll).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->string('type', 20)->default('online')->after('name');
            $table->text('notes')->nullable()->after('config');
        });

        // Update existing online gateways
        DB::table('payment_gateways')->update(['type' => 'online']);

        // Insert offline payment methods
        $now = now();
        DB::table('payment_gateways')->insert([
            [
                'name'        => 'cash',
                'type'        => 'offline',
                'is_active'   => true,
                'environment' => 'production',
                'notes'       => 'Pembayaran tunai diterima langsung oleh petugas di lokasi.',
                'config'      => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'bank_transfer',
                'type'        => 'offline',
                'is_active'   => false,
                'environment' => 'production',
                'notes'       => null,
                'config'      => json_encode([
                    'bank_name'    => '',
                    'account_name' => '',
                    'account_number' => '',
                ]),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('payment_gateways')->whereIn('name', ['cash', 'bank_transfer'])->delete();

        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn(['type', 'notes']);
        });
    }
};
