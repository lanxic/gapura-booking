<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Booking Received</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
    .wrapper { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .header { background: #065f46; padding: 32px 24px; text-align: center; }
    .header h1 { color: #fff; margin: 0; font-size: 22px; }
    .header p { color: #a7f3d0; margin: 6px 0 0; font-size: 14px; }
    .body { padding: 28px 24px; }
    .notice-box { background: #fefce8; border: 1px solid #fde047; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; }
    .notice-box p { color: #713f12; font-size: 14px; margin: 0; line-height: 1.6; }
    .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-row .key { color: #6b7280; }
    .detail-row .val { color: #111827; font-weight: 500; text-align: right; }
    .deadline-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px 20px; margin-top: 20px; text-align: center; }
    .deadline-box .label { font-size: 12px; color: #991b1b; text-transform: uppercase; letter-spacing: 1px; }
    .deadline-box .value { font-size: 18px; font-weight: 700; color: #b91c1c; margin-top: 4px; }
    .cta-btn { display: block; width: fit-content; margin: 24px auto 0; background: #065f46; color: #fff; text-decoration: none; padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 15px; }
    .login-section { margin-top: 24px; padding-top: 20px; border-top: 1px solid #f3f4f6; text-align: center; }
    .login-section p { font-size: 13px; color: #6b7280; margin: 0 0 10px; }
    .login-btn { display: inline-block; background: #f0fdf4; color: #065f46; border: 1px solid #86efac; text-decoration: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; }
    .footer { background: #f9fafb; padding: 16px 24px; text-align: center; font-size: 12px; color: #9ca3af; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>Order Received!</h1>
      <p>Hi {{ $order->customer_name }}, your booking is waiting for payment.</p>
    </div>
    <div class="body">

      <div class="notice-box">
        <p>Your booking has been received. Please complete your payment before the deadline to confirm your reservation. Your booking code will be sent after payment is verified.</p>
      </div>

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
          <span class="key">Total</span>
          <span class="val">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
        </div>
      </div>

      @if($order->expires_at)
      <div class="deadline-box">
        <div class="label">Payment Deadline</div>
        <div class="value">{{ $order->expires_at->format('d M Y, H:i') }}</div>
      </div>
      @endif

      <a href="{{ env('FRONTEND_URL', 'http://localhost:3000') }}/payment?code={{ $order->booking_code }}" class="cta-btn">
        Complete Payment
      </a>

      <div class="login-section">
        <p>Already have an account or want to track your order?</p>
        <a href="{{ env('FRONTEND_URL', 'http://localhost:3000') }}/auth/login" class="login-btn">
          Sign in to My Account
        </a>
        <p style="margin-top: 8px;">
          Don't have an account?
          <a href="{{ env('FRONTEND_URL', 'http://localhost:3000') }}/auth/register" style="color:#065f46;">Register here</a>
          using this email to see your orders.
        </p>
      </div>

    </div>
    <div class="footer">
      This email was sent to {{ $order->customer_email }}.
      Your booking code will be sent once payment is confirmed.
    </div>
  </div>
</body>
</html>
