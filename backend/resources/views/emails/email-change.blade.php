<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: sans-serif; background: #f8f8f8; margin: 0; padding: 40px 20px; }
    .card { background: #fff; max-width: 480px; margin: 0 auto; border-radius: 16px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    h2 { margin: 0 0 16px; color: #1c1917; }
    p { color: #57534e; line-height: 1.6; }
    .btn { display: inline-block; margin-top: 24px; padding: 14px 32px; background: #1c1917; color: #fff; text-decoration: none; border-radius: 999px; font-weight: 600; }
    .note { margin-top: 24px; font-size: 13px; color: #a8a29e; }
  </style>
</head>
<body>
  <div class="card">
    <h2>Confirm your new email</h2>
    <p>Hi {{ $userName }}, click the button below to confirm this as your new email address.</p>
    <a href="{{ $verifyUrl }}" class="btn">Confirm Email</a>
    <p class="note">If you did not request this change, you can safely ignore this email. Your original email address remains active.</p>
  </div>
</body>
</html>
