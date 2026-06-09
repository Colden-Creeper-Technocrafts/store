<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Order Confirmed</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:32px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:100%;">

      {{-- Header --}}
      <tr>
        <td style="background:#111827;padding:24px 32px;">
          <p style="margin:0;color:#ffffff;font-size:22px;font-weight:bold;">{{ $storeName }}</p>
        </td>
      </tr>

      {{-- Hero --}}
      <tr>
        <td style="background:#16a34a;padding:28px 32px;text-align:center;">
          <p style="margin:0;color:#ffffff;font-size:28px;font-weight:bold;">Order Confirmed!</p>
          <p style="margin:8px 0 0;color:#dcfce7;font-size:15px;">Thank you for your purchase, {{ $order->shipping_name }}.</p>
        </td>
      </tr>

      {{-- Body --}}
      <tr>
        <td style="padding:32px;">

          <p style="margin:0 0 8px;color:#374151;font-size:15px;">
            Your order <strong>#{{ $order->id }}</strong> has been placed successfully and is now being processed.
          </p>

          {{-- Order summary box --}}
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;margin:24px 0;padding:0;">
            <tr>
              <td style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
                <p style="margin:0;color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Order Summary</p>
              </td>
            </tr>
            @foreach($order->items as $item)
            <tr>
              <td style="padding:10px 20px;border-bottom:1px solid #f3f4f6;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td style="color:#111827;font-size:14px;">{{ $item->name }} <span style="color:#6b7280;">× {{ $item->quantity }}</span></td>
                    <td align="right" style="color:#111827;font-size:14px;font-weight:bold;">₹{{ number_format($item->subtotal, 2) }}</td>
                  </tr>
                </table>
              </td>
            </tr>
            @endforeach
            @if($order->discount_amount > 0)
            <tr>
              <td style="padding:10px 20px;border-bottom:1px solid #f3f4f6;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td style="color:#16a34a;font-size:14px;">Coupon Discount</td>
                    <td align="right" style="color:#16a34a;font-size:14px;">−₹{{ number_format($order->discount_amount, 2) }}</td>
                  </tr>
                </table>
              </td>
            </tr>
            @endif
            @if($order->shipping_cost > 0)
            <tr>
              <td style="padding:10px 20px;border-bottom:1px solid #f3f4f6;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td style="color:#374151;font-size:14px;">Shipping</td>
                    <td align="right" style="color:#374151;font-size:14px;">₹{{ number_format($order->shipping_cost, 2) }}</td>
                  </tr>
                </table>
              </td>
            </tr>
            @endif
            <tr>
              <td style="padding:14px 20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td style="color:#111827;font-size:15px;font-weight:bold;">Total</td>
                    <td align="right" style="color:#111827;font-size:16px;font-weight:bold;">₹{{ number_format($order->total, 2) }}</td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>

          {{-- Shipping address --}}
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;margin:0 0 24px;">
            <tr>
              <td style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
                <p style="margin:0;color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Shipping To</p>
              </td>
            </tr>
            <tr>
              <td style="padding:14px 20px;color:#374151;font-size:14px;line-height:1.6;">
                {{ $order->shipping_name }}<br>
                {{ $order->shipping_address }}, {{ $order->shipping_city }}<br>
                {{ $order->shipping_state }} — {{ $order->shipping_postal_code }}<br>
                {{ $order->shipping_country }}
              </td>
            </tr>
          </table>

          <p style="margin:0;color:#6b7280;font-size:13px;">
            We'll send you another email when your order ships. If you have any questions, reply to this email or contact us.
          </p>

        </td>
      </tr>

      {{-- Footer --}}
      <tr>
        <td style="background:#f9fafb;padding:16px 32px;text-align:center;border-top:1px solid #e5e7eb;">
          <p style="margin:0;color:#9ca3af;font-size:12px;">&copy; {{ date('Y') }} {{ $storeName }}. All rights reserved.</p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
