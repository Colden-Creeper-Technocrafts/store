<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Return Update</title>
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

      {{-- Return status hero --}}
      @php
        $colors = [
          'approved'  => ['bg' => '#16a34a', 'light' => '#dcfce7', 'text' => 'Return Approved'],
          'rejected'  => ['bg' => '#dc2626', 'light' => '#fee2e2', 'text' => 'Return Rejected'],
          'refunded'  => ['bg' => '#2563eb', 'light' => '#dbeafe', 'text' => 'Refund Processed'],
          'requested' => ['bg' => '#d97706', 'light' => '#fef3c7', 'text' => 'Return Requested'],
        ];
        $rs = $order->return_status ?? 'requested';
        $c  = $colors[$rs] ?? ['bg' => '#374151', 'light' => '#f3f4f6', 'text' => 'Return Update'];
      @endphp
      <tr>
        <td style="background:{{ $c['bg'] }};padding:28px 32px;text-align:center;">
          <p style="margin:0;color:#ffffff;font-size:26px;font-weight:bold;">{{ $c['text'] }}</p>
          <p style="margin:8px 0 0;color:#ffffff;opacity:.85;font-size:14px;">Order #{{ $order->id }}</p>
        </td>
      </tr>

      {{-- Body --}}
      <tr>
        <td style="padding:32px;">

          <p style="margin:0 0 20px;color:#374151;font-size:15px;">
            Hi <strong>{{ $order->shipping_name }}</strong>,
          </p>

          @if($rs === 'approved')
          <p style="margin:0 0 20px;color:#374151;font-size:15px;">
            Your return request for Order #{{ $order->id }} has been <strong style="color:#16a34a;">approved</strong>.
            Please ship the item(s) back as instructed by our team. We will process your refund once we receive the return.
          </p>
          @elseif($rs === 'rejected')
          <p style="margin:0 0 20px;color:#374151;font-size:15px;">
            Unfortunately, your return request for Order #{{ $order->id }} has been <strong style="color:#dc2626;">rejected</strong>.
            @if($order->return_reason)
            <br><br>Reason: {{ $order->return_reason }}
            @endif
          </p>
          @elseif($rs === 'refunded')
          <p style="margin:0 0 20px;color:#374151;font-size:15px;">
            Great news! Your refund for Order #{{ $order->id }} has been <strong style="color:#2563eb;">processed</strong>.
            The amount of <strong>₹{{ number_format($order->total, 2) }}</strong> will be credited back to your original payment method within 5–7 business days.
          </p>
          @else
          <p style="margin:0 0 20px;color:#374151;font-size:15px;">
            Your return request for Order #{{ $order->id }} has been updated.
          </p>
          @endif

          {{-- Order reference box --}}
          <table width="100%" cellpadding="0" cellspacing="0" style="background:{{ $c['light'] }};border:1px solid {{ $c['bg'] }}30;border-radius:6px;margin:0 0 24px;">
            <tr>
              <td style="padding:16px 20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td style="color:#6b7280;font-size:13px;">Order Number</td>
                    <td align="right" style="color:#111827;font-size:14px;font-weight:bold;">#{{ $order->id }}</td>
                  </tr>
                  <tr>
                    <td style="color:#6b7280;font-size:13px;padding-top:6px;">Return Status</td>
                    <td align="right" style="color:{{ $c['bg'] }};font-size:14px;font-weight:bold;padding-top:6px;">{{ ucfirst($rs) }}</td>
                  </tr>
                  <tr>
                    <td style="color:#6b7280;font-size:13px;padding-top:6px;">Order Total</td>
                    <td align="right" style="color:#111827;font-size:14px;padding-top:6px;">₹{{ number_format($order->total, 2) }}</td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>

          <p style="margin:0;color:#6b7280;font-size:13px;">
            If you have any questions about your return, please reply to this email or contact our support team.
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
