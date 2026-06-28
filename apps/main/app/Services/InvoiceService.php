<?php

namespace App\Services;

use App\Models\ProductSlot;
use App\Models\Invoice;
use App\Models\PaymentAttempt;
use App\Models\PaymentGateway;
use App\Models\PaymentPlan;
use App\Models\PromoCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class InvoiceService
{
    public function __construct(private readonly BookingService $bookingService) {}

    /**
     * Buat invoice baru saat checkout submit (PRD Section 13.3 Step 1).
     * Slot di-lock di Redis TTL 2 jam.
     */
    public function createFromCheckout(array $data): Invoice
    {
        $slot = ProductSlot::lockForUpdate()->findOrFail($data['slot_id']);

        if (! $slot->isAvailableFor($data['pax_count'])) {
            throw new \DomainException('Slot tidak tersedia untuk jumlah pax yang dipilih.');
        }

        $lockTtlMinutes = (int) (\App\Models\SystemSetting::get('booking', 'slot_lock_ttl_minutes', 120));

        $invoiceCode = $this->generateInvoiceCode();
        $items = $this->buildItems($slot, $data);
        $subtotal = collect($items)->sum('subtotal');
        $discount = 0;
        $promoCodeId = null;

        if (! empty($data['promo_code'])) {
            [$discount, $promoCodeId] = $this->applyPromo($data['promo_code'], $subtotal);
        }

        $total = max(0, $subtotal - $discount);
        $plan = $this->resolvePlan($data['payment_plan'] ?? 'FULL', $total, $slot->date);
        $dueNow = (int) round($total * $plan->percentage / 100);

        return DB::transaction(function () use (
            $slot, $data, $invoiceCode, $items, $subtotal, $discount,
            $promoCodeId, $total, $plan, $dueNow, $lockTtlMinutes
        ) {
            $invoice = Invoice::create([
                'invoice_code'    => $invoiceCode,
                'customer_id'     => $data['customer_id'] ?? null,
                'guest_name'      => $data['guest_name'],
                'guest_email'     => $data['guest_email'],
                'guest_phone'     => $data['guest_phone'] ?? null,
                'checkout_slot_id'=> $slot->id,
                'pax_count'       => $data['pax_count'],
                'items'           => $items,
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'promo_code_id'   => $promoCodeId,
                'total_amount'    => $total,
                'payment_plan'    => $plan->code,
                'due_now'         => $dueNow,
                'due_later'       => $total - $dueNow,
                'status'          => 'draft',
                'due_at'          => now()->addMinutes($lockTtlMinutes),
            ]);

            // Lock slot di Redis (key = slot:{id}, value = invoice_code)
            Redis::setex("slot_lock:{$slot->id}", $lockTtlMinutes * 60, $invoiceCode);

            if ($promoCodeId) {
                PromoCode::where('id', $promoCodeId)->increment('used_count');
            }

            return $invoice;
        });
    }

    /**
     * Inisialisasi Snap token Midtrans untuk pembayaran online (Step 3).
     * Returns snap_token string untuk digunakan di Snap.js popup.
     */
    public function initiateSnapToken(Invoice $invoice): string
    {
        $gateway = PaymentGateway::where('type', 'online')->where('is_active', true)->first()
            ?? throw new \DomainException('Tidak ada payment gateway online yang aktif.');

        $attemptNumber = $invoice->paymentAttempts()->count() + 1;
        $attemptCode   = sprintf('PAY-%s-%03d', $invoice->invoice_code, $attemptNumber);

        PaymentAttempt::create([
            'attempt_code' => $attemptCode,
            'invoice_id'   => $invoice->id,
            'gateway'      => $gateway->name,
            'gateway_env'  => $gateway->environment,
            'amount'       => $invoice->due_now,
            'status'       => 'pending',
            'attempted_at' => now(),
        ]);

        $invoice->update([
            'status'           => 'pending',
            'gateway'          => $gateway->name,
            'gateway_order_id' => $invoice->invoice_code,
        ]);

        return match ($gateway->name) {
            'midtrans' => $this->getMidtransSnapToken($gateway, $invoice),
            default    => throw new \DomainException("Gateway '{$gateway->name}' belum mendukung Snap checkout."),
        };
    }

    /**
     * Tandai invoice offline sebagai pending + simpan gateway yang dipilih.
     */
    public function markOfflinePayment(Invoice $invoice, string $gatewayName): void
    {
        $gateway = PaymentGateway::where('name', $gatewayName)->where('type', 'offline')->where('is_active', true)->first()
            ?? throw new \DomainException('Metode pembayaran offline tidak tersedia.');

        $invoice->update([
            'status'  => 'pending',
            'gateway' => $gateway->name,
        ]);
    }

    /**
     * Inisialisasi payment request ke gateway aktif (Step 3).
     * @deprecated Gunakan initiateSnapToken() untuk web checkout
     */
    public function initiatePayment(Invoice $invoice): array
    {
        $gateway = PaymentGateway::activeGateway()
            ?? throw new \DomainException('Tidak ada payment gateway yang aktif.');

        $attemptNumber = $invoice->paymentAttempts()->count() + 1;
        $attemptCode   = sprintf('PAY-%s-%03d', $invoice->invoice_code, $attemptNumber);

        $attempt = PaymentAttempt::create([
            'attempt_code'   => $attemptCode,
            'invoice_id'     => $invoice->id,
            'gateway'        => $gateway->name,
            'gateway_env'    => $gateway->environment,
            'amount'         => $invoice->due_now,
            'status'         => 'pending',
            'attempted_at'   => now(),
        ]);

        $invoice->update([
            'status'           => 'pending',
            'gateway'          => $gateway->name,
            'gateway_order_id' => $invoice->invoice_code,
        ]);

        $paymentUrl = $this->dispatchToGateway($gateway, $invoice, $attempt);

        return ['payment_url' => $paymentUrl, 'attempt_code' => $attemptCode];
    }

    /**
     * Proses webhook dari payment gateway (Step 5 PRD 13.3).
     */
    public function handleWebhook(string $gatewayName, array $payload): void
    {
        $gatewayOrderId = $payload['order_id'] ?? $payload['invoice_number'] ?? null;

        $invoice = Invoice::where('gateway_order_id', $gatewayOrderId)->firstOrFail();

        // Idempotency: abaikan jika sudah PAID
        if ($invoice->status === 'paid') return;

        $transactionStatus = $payload['transaction_status'] ?? $payload['status'] ?? 'failure';
        $isSuccess = in_array($transactionStatus, ['capture', 'settlement', 'paid', 'success']);

        $attempt = $invoice->paymentAttempts()
            ->where('gateway', $gatewayName)
            ->whereIn('status', ['pending'])
            ->latest('attempted_at')
            ->first();

        if ($attempt) {
            $attempt->update([
                'gateway_tx_id'  => $payload['transaction_id'] ?? $payload['jt_invoice'] ?? null,
                'payment_method' => $payload['payment_type'] ?? null,
                'status'         => $isSuccess ? 'success' : 'failure',
                'raw_response'   => $payload,
                'settled_at'     => $isSuccess ? now() : null,
            ]);
        }

        if ($isSuccess) {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
            $this->bookingService->createFromInvoice($invoice);
        }
    }

    /**
     * Expire invoice yang melewati due_at (dijalankan via Laravel Scheduler setiap 5 menit).
     */
    public function expireOverdue(): int
    {
        $expired = Invoice::where('status', 'pending')
            ->where('due_at', '<', now())
            ->get();

        foreach ($expired as $invoice) {
            $invoice->update(['status' => 'expired']);
            Redis::del("slot_lock:{$invoice->checkout_slot_id}");
        }

        return $expired->count();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function generateInvoiceCode(): string
    {
        $date = now()->format('Ymd');
        $seq  = Redis::incr("inv_seq:{$date}");
        // TTL reset tiap hari (86400 detik)
        Redis::expire("inv_seq:{$date}", 86400);

        return sprintf('INV-%s-%05d', $date, $seq);
    }

    private function buildItems(ProductSlot $slot, array $data): array
    {
        $items = [[
            'type'       => 'product',
            'name'       => $slot->product->name,
            'unit_price' => $slot->price,
            'quantity'   => $data['pax_count'],
            'subtotal'   => $slot->price * $data['pax_count'],
        ]];

        foreach ($data['addons'] ?? [] as $addon) {
            $addonModel = \App\Models\ProductAddon::find($addon['addon_id']);
            if (! $addonModel) continue;
            $items[] = [
                'type'       => 'addon',
                'name'       => $addonModel->name,
                'unit_price' => $addonModel->price,
                'quantity'   => $addon['quantity'],
                'subtotal'   => $addonModel->price * $addon['quantity'],
            ];
        }

        return $items;
    }

    private function applyPromo(string $code, int $amount): array
    {
        $promo = PromoCode::where('code', strtoupper($code))->first();

        if (! $promo || ! $promo->isValid($amount)) {
            throw new \DomainException('Kode promo tidak valid atau sudah kadaluarsa.');
        }

        return [$promo->calculateDiscount($amount), $promo->id];
    }

    private function resolvePlan(string $code, int $total, mixed $activityDate): PaymentPlan
    {
        // Full payment wajib untuk booking < 3 hari ke depan
        $advanceDays = (int) \App\Models\SystemSetting::get('booking', 'advance_booking_days_dp', 3);
        $isAdvance = now()->diffInDays($activityDate, false) > $advanceDays;

        if (! $isAdvance || $code === 'FULL') {
            return PaymentPlan::where('code', 'FULL')->firstOrFail();
        }

        $plan = PaymentPlan::where('code', $code)->where('is_active', true)->first()
            ?? PaymentPlan::where('code', 'FULL')->firstOrFail();

        if ($plan->min_amount > 0 && $total < $plan->min_amount) {
            return PaymentPlan::where('code', 'FULL')->firstOrFail();
        }

        return $plan;
    }

    private function dispatchToGateway(PaymentGateway $gateway, Invoice $invoice, PaymentAttempt $attempt): string
    {
        return match ($gateway->name) {
            'midtrans' => $this->getMidtransSnapToken($gateway, $invoice),
            default    => throw new \DomainException("Gateway '{$gateway->name}' belum didukung."),
        };
    }

    private function getMidtransSnapToken(PaymentGateway $gateway, Invoice $invoice): string
    {
        \Midtrans\Config::$serverKey    = $gateway->server_key;
        \Midtrans\Config::$isProduction = $gateway->environment === 'production';
        \Midtrans\Config::$isSanitized  = true;
        \Midtrans\Config::$is3ds        = true;

        $items = collect($invoice->items)->map(fn ($item) => [
            'id'       => $item['type'] . '-' . $item['name'],
            'name'     => mb_substr($item['name'], 0, 50),
            'price'    => (int) $item['unit_price'],
            'quantity' => (int) $item['quantity'],
        ])->values()->toArray();

        // Pastikan total item === due_now (untuk menghindari mismatch Midtrans)
        $itemTotal = collect($items)->sum(fn ($i) => $i['price'] * $i['quantity']);
        if ($itemTotal !== (int) $invoice->due_now && (int) $invoice->due_later > 0) {
            $diff = (int) $invoice->due_now - $itemTotal;
            $items[] = ['id' => 'dp-adjustment', 'name' => 'DP Adjustment', 'price' => $diff, 'quantity' => 1];
        }

        $params = [
            'transaction_details' => [
                'order_id'     => $invoice->invoice_code,
                'gross_amount' => (int) $invoice->due_now,
            ],
            'customer_details' => [
                'first_name' => $invoice->guest_name,
                'email'      => $invoice->guest_email,
                'phone'      => $invoice->guest_phone ?? '',
            ],
            'item_details' => $items,
            'expiry' => [
                'unit'     => 'minutes',
                'duration' => max(1, (int) now()->diffInMinutes($invoice->due_at, false)),
            ],
        ];

        try {
            $snap = \Midtrans\Snap::createTransaction($params);
            return $snap->token;
        } catch (\Exception $e) {
            throw new \DomainException('Gagal menginisiasi pembayaran: ' . $e->getMessage());
        }
    }
}
