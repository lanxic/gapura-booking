<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingPaid extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly ?Payment $payment = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Confirmed – ' . $this->order->booking_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-paid',
        );
    }
}
