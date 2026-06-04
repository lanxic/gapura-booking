<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer  = DB::table('users')->where('email', 'customer@amartha.test')->first();
        $customer2 = DB::table('users')->where('email', 'customer2@amartha.test')->first();
        $kasir     = DB::table('users')->where('email', 'kasir@amartha.test')->first();
        $scanner   = DB::table('users')->where('email', 'scanner@amartha.test')->first();

        $variant    = DB::table('product_variants')->where('label', 'Weekday (Selasa–Jumat)')->first();
        $slotToday  = DB::table('availability_slots')->where('product_id', $variant->product_id)->where('date', now()->toDateString())->first();
        $slotNext   = DB::table('availability_slots')->where('product_id', $variant->product_id)->where('date', now()->addDays(3)->toDateString())->first();
        $slotFuture = DB::table('availability_slots')->where('product_id', $variant->product_id)->where('date', now()->addDays(7)->toDateString())->first();

        $variantMalam = DB::table('product_variants')->where('label', 'Regular')->first();
        $slotMalam    = DB::table('availability_slots')->where('product_id', $variantMalam->product_id)->where('date', now()->addDays(2)->toDateString())->first();

        $variantWeekend = DB::table('product_variants')->where('label', 'Weekend (Sabtu–Minggu)')->first();
        $slotWeekend    = DB::table('availability_slots')->where('product_id', $variantWeekend->product_id)->where('date', now()->addDays(5)->toDateString())->first();

        // ── 1. Full Payment — lunas, 2 tiket belum scan ──────────────────────
        $order1 = $this->insertOrder([
            'booking_code'     => 'AMT-FULL0001',
            'user_id'          => $customer->id,
            'customer_name'    => $customer->name,
            'customer_email'   => $customer->email,
            'customer_phone'   => '081234567890',
            'payment_type'     => 'full',
            'dp_percent'       => null,
            'dp_amount'        => null,
            'remaining_amount' => 0,
            'status'           => OrderStatus::Paid->value,
            'subtotal'         => 370000,
            'discount'         => 0,
            'total'            => 370000,
            'expires_at'       => now()->addMinutes(10),
        ]);
        $item1 = $this->insertOrderItem($order1, $variant->id, $slotToday->id, 2, 0, 185000, 0, 370000);
        // Invoice dikirim ke Midtrans — BERBEDA dari booking_code
        $this->insertPayment($order1, 'INV-20260604-FL0001', 'midtrans', 'full', 370000, 'success', now()->subHour());
        $t1 = $this->insertTicket($item1, TicketStatus::Unused); $this->insertOrderItemTicket($item1, $t1);
        $t2 = $this->insertTicket($item1, TicketStatus::Unused); $this->insertOrderItemTicket($item1, $t2);
        $this->incrementBooked($slotToday->id, 2);

        // ── 2. DP 30% — tiket aktif, sisa Rp 259.000 di kasir ───────────────
        $order2 = $this->insertOrder([
            'booking_code'     => 'AMT-DP300001',
            'user_id'          => $customer->id,
            'customer_name'    => $customer->name,
            'customer_email'   => $customer->email,
            'customer_phone'   => '081234567890',
            'payment_type'     => 'down_payment',
            'dp_percent'       => 30,
            'dp_amount'        => 111000,
            'remaining_amount' => 259000,
            'status'           => OrderStatus::DpPaid->value,
            'subtotal'         => 370000,
            'discount'         => 0,
            'total'            => 370000,
            'expires_at'       => now()->addMinutes(10),
        ]);
        $item2 = $this->insertOrderItem($order2, $variant->id, $slotNext->id, 2, 0, 185000, 0, 370000);
        // Invoice DP — prefix INV-{date}-DP untuk mudah dibedakan
        $this->insertPayment($order2, 'INV-20260604-DP0001', 'midtrans', 'dp', 111000, 'success', now()->subMinutes(30));
        $t3 = $this->insertTicket($item2, TicketStatus::Unused); $this->insertOrderItemTicket($item2, $t3);
        $t4 = $this->insertTicket($item2, TicketStatus::Unused); $this->insertOrderItemTicket($item2, $t4);
        $this->incrementBooked($slotNext->id, 2);

        // ── 3. DP 50% sudah lunas kasir — ada 2 invoice (DP online + pelunasan cash) ──
        $total3  = 225000 + 165000; // 1 dewasa + 1 anak
        $dp3     = (int) ceil($total3 * 0.5);
        $sisa3   = $total3 - $dp3;

        $order3 = $this->insertOrder([
            'booking_code'     => 'AMT-DP500001',
            'user_id'          => $customer2->id,
            'customer_name'    => $customer2->name,
            'customer_email'   => $customer2->email,
            'customer_phone'   => '089876543210',
            'payment_type'     => 'down_payment',
            'dp_percent'       => 50,
            'dp_amount'        => $dp3,
            'remaining_amount' => 0,
            'status'           => OrderStatus::Paid->value,
            'subtotal'         => $total3,
            'discount'         => 0,
            'total'            => $total3,
            'expires_at'       => now()->addMinutes(10),
        ]);
        $item3 = $this->insertOrderItem($order3, $variantWeekend->id, $slotWeekend->id, 1, 1, 225000, 165000, $total3);
        // Invoice 1: DP via Midtrans
        $this->insertPayment($order3, 'INV-20260604-DP0002', 'midtrans', 'dp', $dp3, 'success', now()->subHours(2));
        // Invoice 2: Pelunasan cash di kasir — tidak ada snap_token, pakai invoice terpisah
        $this->insertPayment($order3, 'INV-20260604-CS0001', 'cash', 'remaining', $sisa3, 'success', now()->subHour(), $kasir->id);
        $t5 = $this->insertTicket($item3, TicketStatus::Unused); $this->insertOrderItemTicket($item3, $t5);
        $t6 = $this->insertTicket($item3, TicketStatus::Unused); $this->insertOrderItemTicket($item3, $t6);
        $this->incrementBooked($slotWeekend->id, 2);

        // ── 4. Full Payment — tiket sudah di-scan ────────────────────────────
        $order4 = $this->insertOrder([
            'booking_code'     => 'AMT-SCN00001',
            'user_id'          => null,
            'customer_name'    => 'Tamu Walk-in',
            'customer_email'   => 'walkin@example.com',
            'customer_phone'   => '082211223344',
            'payment_type'     => 'full',
            'dp_percent'       => null,
            'dp_amount'        => null,
            'remaining_amount' => 0,
            'status'           => OrderStatus::Paid->value,
            'subtotal'         => 185000,
            'discount'         => 0,
            'total'            => 185000,
            'expires_at'       => now()->addMinutes(10),
        ]);
        $item4 = $this->insertOrderItem($order4, $variant->id, $slotToday->id, 1, 0, 185000, 0, 185000);
        $this->insertPayment($order4, 'INV-20260604-FL0002', 'midtrans', 'full', 185000, 'success', now()->subHours(3));
        $t7 = $this->insertTicket($item4, TicketStatus::Used, now()->subMinutes(45), $scanner->id);
        $this->insertOrderItemTicket($item4, $t7);
        $this->incrementBooked($slotToday->id, 1);

        // ── 5. Pending — belum bayar, hampir expire ───────────────────────────
        $order5 = $this->insertOrder([
            'booking_code'     => 'AMT-PND00001',
            'user_id'          => $customer2->id,
            'customer_name'    => $customer2->name,
            'customer_email'   => $customer2->email,
            'customer_phone'   => '089876543210',
            'payment_type'     => 'full',
            'dp_percent'       => null,
            'dp_amount'        => null,
            'remaining_amount' => 0,
            'status'           => OrderStatus::Pending->value,
            'subtotal'         => 250000,
            'discount'         => 0,
            'total'            => 250000,
            'expires_at'       => now()->addMinutes(8),
        ]);
        $this->insertOrderItem($order5, $variantMalam->id, $slotMalam->id, 1, 0, 250000, 0, 250000);
        // Belum ada payment — snap_token belum digenerate saat ini
        $this->incrementBooked($slotMalam->id, 1);

        // ── 6. Expired ────────────────────────────────────────────────────────
        $order6 = $this->insertOrder([
            'booking_code'     => 'AMT-EXP00001',
            'user_id'          => null,
            'customer_name'    => 'Andi Wijaya',
            'customer_email'   => 'andi@example.com',
            'customer_phone'   => '087700112233',
            'payment_type'     => 'full',
            'dp_percent'       => null,
            'dp_amount'        => null,
            'remaining_amount' => 0,
            'status'           => OrderStatus::Expired->value,
            'subtotal'         => 185000,
            'discount'         => 0,
            'total'            => 185000,
            'expires_at'       => now()->subMinutes(5),
        ]);
        $this->insertOrderItem($order6, $variant->id, $slotFuture->id, 1, 0, 185000, 0, 185000);

        // ── 7. Full Payment + Voucher + retry payment (2 invoice Midtrans) ────
        // Simulasi: customer gagal bayar pertama kali, lalu berhasil di percobaan ke-2
        $voucher   = DB::table('vouchers')->where('code', 'HEMAT50K')->first();
        $subtotal7 = 185000 * 3;
        $discount7 = 50000;
        $total7    = $subtotal7 - $discount7;

        $order7 = $this->insertOrder([
            'booking_code'     => 'AMT-VOC00001',
            'user_id'          => $customer->id,
            'customer_name'    => $customer->name,
            'customer_email'   => $customer->email,
            'customer_phone'   => '081234567890',
            'payment_type'     => 'full',
            'dp_percent'       => null,
            'dp_amount'        => null,
            'remaining_amount' => 0,
            'status'           => OrderStatus::Paid->value,
            'subtotal'         => $subtotal7,
            'discount'         => $discount7,
            'total'            => $total7,
            'expires_at'       => now()->addMinutes(10),
        ]);
        $item7 = $this->insertOrderItem($order7, $variant->id, $slotFuture->id, 3, 0, 185000, 0, $subtotal7);
        // Invoice percobaan pertama — FAILED (expired di Midtrans)
        $this->insertPayment($order7, 'INV-20260604-FL0003', 'midtrans', 'full', $total7, 'expired', now()->subHours(2));
        // Invoice percobaan kedua — SUCCESS, invoice number baru
        $this->insertPayment($order7, 'INV-20260604-FL0004', 'midtrans', 'full', $total7, 'success', now()->subHours(1));

        DB::table('order_voucher')->insert([
            'order_id'        => $order7,
            'voucher_id'      => $voucher->id,
            'discount_amount' => $discount7,
            'applied_at'      => now()->subHours(2),
        ]);
        DB::table('vouchers')->where('id', $voucher->id)->increment('used_count');

        for ($i = 0; $i < 3; $i++) {
            $t = $this->insertTicket($item7, TicketStatus::Unused);
            $this->insertOrderItemTicket($item7, $t);
        }
        $this->incrementBooked($slotFuture->id, 3);

        $this->command->info('  Orders & payments seeded:');
        $this->command->table(
            ['Booking Code', 'Invoice(s)', 'Status', 'Keterangan'],
            [
                ['AMT-FULL0001', 'INV-20260604-FL0001',              'paid',       '2 tiket full payment'],
                ['AMT-DP300001', 'INV-20260604-DP0001',              'dp_paid',    'DP 30%, sisa Rp 259.000'],
                ['AMT-DP500001', 'INV-..DP0002 + INV-..CS0001',      'paid',       'DP 50% + lunas kasir (2 invoice)'],
                ['AMT-SCN00001', 'INV-20260604-FL0002',              'paid',       'Tiket sudah di-scan'],
                ['AMT-PND00001', '(belum ada)',                       'pending',    'Belum bayar ~8 menit'],
                ['AMT-EXP00001', '(belum ada)',                       'expired',    'Order kadaluarsa'],
                ['AMT-VOC00001', 'INV-..FL0003(fail)+FL0004(ok)',    'paid',       '3 tiket, voucher HEMAT50K, retry payment'],
            ]
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function insertOrder(array $data): int
    {
        return DB::table('orders')->insertGetId(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function insertOrderItem(int $orderId, int $variantId, int $slotId, int $qtyAdult, int $qtyChild, int $priceAdult, int $priceChild, int $subtotal): int
    {
        return DB::table('order_items')->insertGetId([
            'order_id'         => $orderId,
            'variant_id'       => $variantId,
            'slot_id'          => $slotId,
            'qty_adult'        => $qtyAdult,
            'qty_child'        => $qtyChild,
            'unit_price_adult' => $priceAdult,
            'unit_price_child' => $priceChild,
            'subtotal'         => $subtotal,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    private function insertPayment(int $orderId, string $invoiceNumber, string $gateway, string $type, int $amount, string $status, \DateTimeInterface $paidAt, ?int $collectedBy = null): void
    {
        DB::table('payments')->insert([
            'order_id'       => $orderId,
            'invoice_number' => $invoiceNumber,
            'gateway'        => $gateway,
            'snap_token'     => $gateway === 'midtrans' ? 'tok_test_' . substr(md5($invoiceNumber), 0, 16) : null,
            'ref_id'         => 'REF-' . strtoupper(substr(md5($invoiceNumber), 0, 8)),
            'payment_type'   => $type,
            'amount'         => $amount,
            'status'         => $status,
            'paid_at'        => $paidAt,
            'payload'        => null,
            'collected_by'   => $collectedBy,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    private function insertTicket(int $orderItemId, TicketStatus $status, ?\DateTimeInterface $usedAt = null, ?int $scannedBy = null): int
    {
        $qrPayload = base64_encode('ticket:' . $orderItemId . ':' . now()->timestamp . ':' . Str::random(8));
        $hmac      = hash_hmac('sha256', $qrPayload, config('app.key', 'test-key'));

        return DB::table('tickets')->insertGetId([
            'order_item_id'      => $orderItemId,
            'qr_code'            => $qrPayload . ':' . $hmac,
            'cloudinary_pdf_id'  => null,
            'cloudinary_pdf_url' => null,
            'status'             => $status->value,
            'used_at'            => $usedAt,
            'scanned_by'         => $scannedBy,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    private function insertOrderItemTicket(int $orderItemId, int $ticketId): void
    {
        DB::table('order_item_tickets')->insert([
            'order_item_id' => $orderItemId,
            'ticket_id'     => $ticketId,
            'seat_label'    => null,
        ]);
    }

    private function incrementBooked(int $slotId, int $qty): void
    {
        DB::table('availability_slots')->where('id', $slotId)->increment('booked_qty', $qty);
    }
}
