<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 20);
            $table->enum('payment_type', ['full', 'down_payment']);
            $table->unsignedSmallInteger('dp_percent')->nullable();
            $table->unsignedInteger('dp_amount')->nullable();
            $table->unsignedInteger('remaining_amount')->default(0);
            $table->enum('status', [
                'pending', 'awaiting_payment', 'dp_paid', 'paid',
                'confirmed', 'cancelled', 'refunded', 'expired',
            ])->default('pending');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total');
            $table->text('notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants');
            $table->foreignId('slot_id')->constrained('availability_slots');
            $table->unsignedSmallInteger('qty_adult')->default(0);
            $table->unsignedSmallInteger('qty_child')->default(0);
            $table->unsignedInteger('unit_price_adult');
            $table->unsignedInteger('unit_price_child')->default(0);
            $table->unsignedInteger('subtotal');
            $table->timestamps();
        });

        Schema::create('order_addon_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('addon_id')->constrained();
            $table->unsignedSmallInteger('qty');
            $table->unsignedInteger('unit_price');
            $table->timestamps();
        });

        Schema::create('order_voucher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_id')->constrained();
            $table->unsignedInteger('discount_amount');
            $table->timestamp('applied_at');
            $table->unique(['order_id', 'voucher_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('gateway', ['midtrans', 'cash']);
            $table->string('snap_token')->nullable();
            $table->string('ref_id')->nullable();
            $table->enum('payment_type', ['dp', 'full', 'remaining']);
            $table->unsignedInteger('amount');
            $table->enum('status', ['pending', 'success', 'failed', 'expired', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->string('qr_code')->unique();
            $table->string('cloudinary_pdf_id')->nullable();
            $table->string('cloudinary_pdf_url')->nullable();
            $table->enum('status', ['unused', 'used', 'expired', 'cancelled'])->default('unused');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('order_item_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('seat_label')->nullable();
            $table->unique(['order_item_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_tickets');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_voucher');
        Schema::dropIfExists('order_addon_items');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
