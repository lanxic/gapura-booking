<?php

namespace App\Jobs;

use App\Mail\BookingPaid;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBookingPaidEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly Order $order,
        public readonly ?Payment $payment = null,
    ) {}

    public function handle(): void
    {
        $cfg = SiteSetting::getGroup('email');

        if (empty($cfg['host']) && ($cfg['mailer'] ?? 'smtp') === 'smtp') {
            return;
        }

        $scheme = match ($cfg['encryption'] ?? '') {
            'ssl'   => 'smtps',
            'tls'   => 'smtp',
            default => null,
        };

        config([
            'mail.default'               => $cfg['mailer']      ?? 'smtp',
            'mail.mailers.smtp.host'     => $cfg['host']        ?? '',
            'mail.mailers.smtp.port'     => (int) ($cfg['port'] ?? 587),
            'mail.mailers.smtp.username' => $cfg['username']    ?? null,
            'mail.mailers.smtp.password' => $cfg['password']    ?? null,
            'mail.mailers.smtp.scheme'   => $scheme,
            'mail.from.address'          => $cfg['from_address'] ?? 'noreply@example.com',
            'mail.from.name'             => $cfg['from_name']   ?? config('app.name'),
        ]);

        app('mail.manager')->purge('smtp');

        Mail::to($this->order->customer_email)
            ->send(new BookingPaid($this->order, $this->payment));
    }
}
