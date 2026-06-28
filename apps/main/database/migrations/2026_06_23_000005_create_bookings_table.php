<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Booking dibuat HANYA setelah pembayaran dikonfirmasi via webhook (Section 13).
// booking_code = ACT-YYYYMMDD-XXXXX (generated via Redis atomic INCR)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 25)->unique(); // ACT-20260610-00015

            // Relasi 1-to-1 dengan invoice (yang dibuat lebih dulu)
            $table->foreignId('invoice_id')->unique()->constrained('invoices');
            $table->foreignId('slot_id')->constrained('product_slots');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // Data tamu (disalin dari invoice agar tetap ada walau customer hapus akun)
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone', 20)->nullable();
            $table->unsignedSmallInteger('pax_count');

            $table->enum('status', ['pending', 'confirmed', 'attended', 'cancelled', 'no_show'])
                  ->default('confirmed');

            $table->text('notes')->nullable();

            // Summary pembayaran
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('paid');

            // QR Code untuk check-in (dibuat saat booking confirmed)
            $table->string('qr_code_token', 100)->unique();   // random token untuk decode
            $table->string('qr_code_path', 500)->nullable();  // path QR image di S3/R2

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'slot_id']);
            $table->index(['customer_id', 'status']);
            $table->index('booking_code');
        });

        Schema::create('booking_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('addon_id')->constrained('product_addons');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedBigInteger('unit_price'); // snapshot harga saat booking
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();

            $table->unique(['booking_id', 'addon_id']);
        });

        Schema::create('booking_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_participants');
        Schema::dropIfExists('booking_addons');
        Schema::dropIfExists('bookings');
    }
};
