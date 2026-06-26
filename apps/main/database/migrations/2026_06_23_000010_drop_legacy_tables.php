<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus semua tabel dari domain lama (products/orders/tickets).
 * Domain baru menggunakan: activities, activity_slots, invoices, bookings,
 * payment_gateways, payment_plans, storage_providers, system_settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Order-domain tables (leaf → root)
        Schema::dropIfExists('order_item_tickets');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_addon_items');
        Schema::dropIfExists('order_voucher');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('vouchers');

        // Product-domain tables
        Schema::dropIfExists('product_addon');
        Schema::dropIfExists('addons');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('availability_slots');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');

        // Audit / operational tables (replaced by new domain)
        Schema::dropIfExists('correction_requests');
        Schema::dropIfExists('user_roles');

        // Settings (replaced by system_settings, payment_gateways, storage_providers)
        Schema::dropIfExists('payment_gateway_configs');
        Schema::dropIfExists('site_settings');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Intentionally not reversible — legacy tables are permanently removed.
    }
};
