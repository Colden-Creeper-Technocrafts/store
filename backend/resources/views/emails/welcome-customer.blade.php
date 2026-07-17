<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Your Kirva Account</title>
  <style>
    body { margin: 0; padding: 0; background: #f5f5f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1e1e1e; }
    .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
    .header { background: #1e293b; padding: 32px 40px; }
    .header h1 { margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.3px; }
    .body { padding: 36px 40px; }
    .body p { margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #374151; }
    .credentials { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px 24px; margin: 24px 0; }
    .credentials p { margin: 6px 0; font-size: 14px; color: #475569; }
    .credentials strong { color: #0f172a; }
    .btn { display: inline-block; margin-top: 8px; padding: 12px 28px; background: #1e293b; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 600; }
    .note { font-size: 13px !important; color: #94a3b8 !important; }
    .footer { border-top: 1px solid #f1f5f9; padding: 20px 40px; text-align: center; font-size: 12px; color: #94a3b8; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>Kirva</h1>
    </div>
    <div class="body">
      <p>Hi {{ $userName }},</p>
      <p>An account has been created for you on Kirva. Here are your login credentials:</p>
      <div class="credentials">
        <p><strong>Email:</strong> {{ $userEmail }}</p>
        <p><strong>Temporary Password:</strong> {{ $plainPassword }}</p>
      </div>
      <a href="{{ env('APP_FRONTEND_URL') }}/login" class="btn">Log In to Your Account</a>
      <p style="margin-top: 24px;" class="note">For your security, please change your password after your first login. If you did not expect this email, please contact us.</p>
    </div>
    <div class="footer">
      &copy; {{ date('Y') }} Kirva. All rights reserved.
    </div>
  </div>
</body>
</html>
