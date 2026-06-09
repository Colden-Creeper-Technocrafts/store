<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Order Update</title>
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

      {{-- Status hero --}}
      @php
        $colors = [
          'processing' => ['bg' => '#2563eb', 'light' => '#dbeafe'],
          'shipped'    => ['bg' => '#7c3aed', 'light' => '#ede9fe'],
          'delivered'  => ['bg' => '#16a34a', 'light' => '#dcfce7'],
          'cancelled'  => ['bg' => '#dc2626', 'light' => '#fee2e2'],
        ];
        $c = $colors[$order->status] ?? ['bg' => '#374151', 'light' => '#f3f4f6'];
        $label = ucfirst($order->status);
      @endphp
      <tr>
        <td style="background:{{ $c['bg'] }};padding:28px 32px;text-align:center;">
          <p style="margin:0;color:#ffffff;font-size:26px;font-weight:bold;">Order {{ $label }}</p>
          <p style="margin:8px 0 0;color:#ffffff;opacity:.85;font-size:14px;">Order #{{ $order->id }}</p>
        </td>
      </tr>

      {{-- Body --}}
      <tr>
        <td style="padding:32px;">

          <p style="margin:0 0 20px;color:#374151;font-size:15px;">
            Hi <strong>{{ $order->shipping_name }}</strong>, your order status has been updated to
            <strong style="color:{{ $c['bg'] }};">{{ $label }}</strong>.
          </p>

          @if($order->status === 'shipped' && $order->tracking_number)
          <table width="100%" cellpadding="0" cellspacing="0" style="background:{{ $c['light'] }};border:1px solid {{ $c['bg'] }}30;border-radius:6px;margin:0 0 24px;">
            <tr>
              <td style="padding:18px 20px;">
                <p style="margin:0 0 6px;color:#374151;font-size:13px;font-weight:bold;text-transform:uppercase;letter-spacing:.5px;">Tracking Information</p>
                <p style="margin:0;color:#111827;font-size:15px;font-weight:bold;">{{ $order->tracking_number }}</p>
                @if($order->tracking_url)
                <p style="margin:8px 0 0;">
                  <a href="{{ $order->tracking_url }}" style="color:{{ $c['bg'] }};font-size:14px;text-decoration:none;font-weight:bold;">Track Your Package &rarr;</a>
                </p>
                @endif
              </td>
            </tr>
          </table>
          @endif

          @if($order->status === 'delivered')
          <p style="margin:0 0 20px;color:#374151;font-size:15px;">
            Your package has been delivered. We hope you enjoy your purchase! If you have any issues, please don't hesitate to contact us.
          </p>
          @elseif($order->status === 'cancelled')
          <p style="margin:0 0 20px;color:#374151;font-size:15px;">
            Your order has been cancelled. If you have any questions about this cancellation, please contact us.
          </p>
          @elseif($order->status === 'processing')
          <p style="margin:0 0 20px;color:#374151;font-size:15px;">
            We're preparing your order for dispatch. You'll receive another update when it ships.
          </p>
          @endif

          {{-- Order summary --}}
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;">
            <tr>
              <td style="padding:12px 20px;border-bottom:1px solid #e5e7eb;">
                <p style="margin:0;color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Order Details</p>
              </td>
            </tr>
            @foreach($order->items as $item)
            <tr>
              <td style="padding:10px 20px;border-bottom:1px solid #f3f4f6;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td style="color:#374151;font-size:14px;">{{ $item->name }} <span style="color:#9ca3af;">× {{ $item->quantity }}</span></td>
                    <td align="right" style="color:#111827;font-size:14px;">₹{{ number_format($item->subtotal, 2) }}</td>
                  </tr>
                </table>
              </td>
            </tr>
            @endforeach
            <tr>
              <td style="padding:12px 20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td style="color:#111827;font-size:15px;font-weight:bold;">Total</td>
                    <td align="right" style="color:#111827;font-size:15px;font-weight:bold;">₹{{ number_format($order->total, 2) }}</td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>

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
