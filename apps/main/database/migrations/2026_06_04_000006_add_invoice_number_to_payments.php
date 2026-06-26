<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah format booking_code agar lebih readable (AMT-XXXXXXXX)
        Schema::table('orders', function (Blueprint $table) {
            $table->string('booking_code', 20)->change();
        });

        // Tambah invoice_number di payments — ini yang dikirim ke Midtrans sebagai order_id
        // nullable karena pembayaran cash tidak punya invoice number
        Schema::table('payments', function (Blueprint $table) {
            $table->string('invoice_number', 30)->nullable()->unique()->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn('invoice_number');
        });
    }
};
