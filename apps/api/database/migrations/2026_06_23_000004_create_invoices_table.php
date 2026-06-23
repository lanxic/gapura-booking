<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Section 13.4.1 — Invoice dibuat saat checkout submit, SEBELUM pembayaran.
// Identitas finansial terpisah dari Booking ID.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_code', 25)->unique(); // INV-YYYYMMDD-00042

            // Customer (nullable untuk guest checkout)
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone', 20)->nullable();

            // Slot yang dipesan
            $table->foreignId('checkout_slot_id')->constrained('activity_slots');
            $table->unsignedSmallInteger('pax_count');

            // Snapshot harga saat checkout (tidak berubah meski harga naik)
            $table->json('items'); // [{type, name, unit_price, quantity, subtotal}]
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes')->nullOnDelete();
            $table->unsignedBigInteger('total_amount');

            // Payment plan
            $table->enum('payment_plan', ['FULL', 'DP30', 'DP50', 'DP70'])->default('FULL');
            $table->unsignedBigInteger('due_now'); // jumlah yang harus dibayar sekarang
            $table->unsignedBigInteger('due_later')->default(0); // sisa pelunasan

            $table->enum('status', ['draft', 'pending', 'paid', 'expired', 'failed', 'refunded'])
                  ->default('draft');

            $table->string('pdf_path', 500)->nullable();

            // Slot di-lock selama 2 jam saat invoice aktif
            $table->timestamp('due_at'); // created_at + 2 jam (configurable via system_settings)
            $table->timestamp('paid_at')->nullable();

            // Gateway info (snapshot saat transaksi, tidak berubah walau gateway di-switch)
            $table->string('gateway', 50)->nullable();
            $table->string('gateway_order_id', 100)->unique()->nullable();

            $table->timestamps();

            $table->index(['status', 'due_at']);
            $table->index('customer_id');
            $table->index('gateway_order_id');
        });

        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('attempt_code', 35)->unique(); // PAY-{invoice_code}-001

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('gateway', 50); // midtrans | doku
            $table->string('gateway_tx_id', 150)->unique()->nullable(); // transaction ID dari gateway
            $table->string('payment_method', 50)->nullable(); // gopay | bca_va | credit_card | qris
            $table->string('gateway_env', 20)->nullable(); // sandbox | production
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('gateway_fee')->default(0); // MDR/processing fee

            $table->enum('status', ['pending', 'success', 'failure', 'expired', 'challenge'])
                  ->default('pending');

            $table->json('raw_request')->nullable();  // payload ke gateway
            $table->json('raw_response')->nullable(); // webhook payload dari gateway (audit trail)

            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamp('settled_at')->nullable();

            $table->index(['invoice_id', 'status']);
            $table->index('gateway_tx_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('invoices');
    }
};
