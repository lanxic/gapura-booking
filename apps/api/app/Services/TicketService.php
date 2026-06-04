<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use Illuminate\Support\Facades\Hash;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TicketService
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function generateForOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            $totalPax = $item->qty_adult + $item->qty_child;
            for ($i = 0; $i < $totalPax; $i++) {
                $this->createTicket($order, $item);
            }
        }
    }

    private function createTicket(Order $order, OrderItem $item): Ticket
    {
        $payload  = "{$order->booking_code}:{$item->id}:" . now()->timestamp . ':' . uniqid();
        $hmac     = hash_hmac('sha256', $payload, config('app.key'));
        $qrCode   = base64_encode("{$payload}:{$hmac}");

        $qrImage  = QrCode::format('png')->size(300)->generate($qrCode);
        $tmpPath  = tempnam(sys_get_temp_dir(), 'ticket_') . '.png';
        file_put_contents($tmpPath, $qrImage);

        return Ticket::create([
            'order_item_id' => $item->id,
            'qr_code'       => $qrCode,
            'status'        => TicketStatus::Unused,
        ]);
    }

    public function verifyQrCode(string $qrCode): array
    {
        $decoded  = base64_decode($qrCode);
        $parts    = explode(':', $decoded);

        if (count($parts) < 4) {
            return ['valid' => false, 'reason' => 'Format QR tidak valid.'];
        }

        $hmac    = array_pop($parts);
        $payload = implode(':', $parts);
        $expected = hash_hmac('sha256', $payload, config('app.key'));

        if (!hash_equals($expected, $hmac)) {
            return ['valid' => false, 'reason' => 'QR code tidak valid (signature mismatch).'];
        }

        $ticket = Ticket::where('qr_code', $qrCode)->with('orderItem.order')->first();

        if (!$ticket) {
            return ['valid' => false, 'reason' => 'Tiket tidak ditemukan.'];
        }

        if ($ticket->status === TicketStatus::Used) {
            return ['valid' => false, 'reason' => 'Tiket sudah digunakan.'];
        }

        if ($ticket->status === TicketStatus::Expired || $ticket->status === TicketStatus::Cancelled) {
            return ['valid' => false, 'reason' => 'Tiket sudah tidak berlaku.'];
        }

        $order = $ticket->orderItem->order;
        $slot  = $ticket->orderItem->slot;

        if ($slot && $slot->date !== now()->toDateString()) {
            return ['valid' => false, 'reason' => 'Tanggal kunjungan tidak sesuai.'];
        }

        $hasRemaining = $order->remaining_amount > 0;

        return [
            'valid'                => true,
            'ticket'               => $ticket,
            'order'                => $order,
            'has_remaining_payment' => $hasRemaining,
            'remaining_amount'     => $order->remaining_amount,
        ];
    }
}
