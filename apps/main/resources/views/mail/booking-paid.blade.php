<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Payment Confirmed</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
    .wrapper { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .header { background: #065f46; padding: 32px 24px; text-align: center; }
    .header h1 { color: #fff; margin: 0; font-size: 22px; }
    .header p { color: #a7f3d0; margin: 6px 0 0; font-size: 14px; }
    .body { padding: 28px 24px; }
    .code-box { background: #ecfdf5; border: 2px dashed #34d399; border-radius: 10px; padding: 20px; text-align: center; margin-bottom: 20px; }
    .code-box .label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
    .code-box .code { font-size: 32px; font-weight: 700; color: #065f46; font-family: monospace; letter-spacing: 6px; margin-top: 6px; }
    .invoice-title { font-size: 13px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; margin: 20px 0 8px; }
    .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-row .key { color: #6b7280; }
    .detail-row .val { color: #111827; font-weight: 500; text-align: right; }
    .total-row { display: flex; justify-content: space-between; padding: 10px 0 0; font-size: 15px; font-weight: 700; }
    .total-row .val { color: #065f46; }
    .steps { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 16px 20px; margin-top: 20px; }
    .steps h3 { color: #92400e; margin: 0 0 10px; font-size: 14px; }
    .steps ol { color: #92400e; font-size: 13px; margin: 0; padding-left: 18px; line-height: 1.8; }
    .cta-btn { display: block; width: fit-content; margin: 24px auto 0; background: #065f46; color: #fff; text-decoration: none; padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 15px; }
    .footer { background: #f9fafb; padding: 16px 24px; text-align: center; font-size: 12px; color: #9ca3af; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>Payment Confirmed!</h1>
      <p>Thank you {{ $order->customer_name }}, your payment has been verified.</p>
    </div>
    <div class="body">

      <div class="code-box">
        <div class="label">Booking Code</div>
        <div class="code">{{ $order->booking_code }}</div>
      </div>

      <div class="invoice-title">Invoice Detail</div>
      <div>
        <div class="detail-row">
          <span class="key">Name</span>
          <span class="val">{{ $order->customer_name }}</span>
        </div>
        <div class="detail-row">
          <span class="key">Email</span>
          <span class="val">{{ $order->customer_email }}</span>
        </div>
        <div class="detail-row">
          <span class="key">Invoice No.</span>
          <span class="val">{{ $payment->invoice_number ?? '-' }}</span>
        </div>
        <div class="detail-row">
          <span class="key">Payment Method</span>
          <span class="val">{{ ucfirst($payment->gateway ?? '-') }}</span>
        </div>
        <div class="detail-row">
          <span class="key">Amount Paid</span>
          <span class="val">Rp {{ number_format($payment->amount ?? $order->total, 0, ',', '.') }}</span>
        </div>
        @if($order->discount > 0)
        <div class="detail-row">
          <span class="key">Discount</span>
          <span class="val">- Rp {{ number_format($order->discount, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="total-row">
          <span class="key">Total</span>
          <span class="val">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
        </div>
      </div>

      <div class="steps">
        <h3>Next Steps</h3>
        <ol>
          <li>Your e-ticket with QR code has been generated and will be available in your account.</li>
          <li>Sign in to your account to view and download your ticket.</li>
          <li>Show the QR code when checking in at the venue.</li>
        </ol>
      </div>

      <a href="{{ env('FRONTEND_URL', 'http://localhost:3000') }}/account" class="cta-btn">
        View My Tickets
      </a>

    </div>
    <div class="footer">
      This email was sent to {{ $order->customer_email }}.
      Please keep your booking code <strong>{{ $order->booking_code }}</strong> safe.
    </div>
  </div>
</body>
</html>
