<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Winning Heaven Verification</title>
</head>
<body style="margin:0;padding:0;background:#030a12;color:#ecf4ff;font-family:Helvetica,Arial,sans-serif">
  <div style="max-width:520px;margin:40px auto;background:#0a1522;border:1px solid rgba(62,224,178,.45);border-radius:18px;overflow:hidden;box-shadow:0 16px 40px rgba(0,0,0,.55)">
    <div style="padding:28px 24px;text-align:center;border-bottom:1px solid rgba(76,201,240,.18);background:linear-gradient(135deg,#0c1a28,#061018)">
      <div style="font-size:22px;font-weight:900;letter-spacing:2px">
        <span style="color:#3ee0b2">WINNING</span>
        <span style="color:#ffe2a8"> HEAVEN</span>
      </div>
    </div>
    <div style="padding:36px 28px">
      <h1 style="margin:0 0 10px;font-size:20px;color:#fff">{{ $heading }}</h1>
      <p style="margin:0 0 22px;color:#9bb0c7;font-size:14px;line-height:1.6">
        Hi{{ $name ? ' '.$name : '' }}, use this one-time code to {{ $purposeLabel }}. It expires in 10 minutes.
      </p>
      <div style="background:#030a12;border:1px dashed rgba(62,224,178,.45);border-radius:14px;padding:22px;text-align:center;margin:24px 0">
        <div style="font-size:34px;font-weight:900;letter-spacing:8px;color:#3ee0b2;font-family:monospace">{{ $otp }}</div>
        <div style="margin-top:10px;font-size:11px;color:#ff6b7a;font-weight:700">Do not share this code with anyone.</div>
      </div>
      <p style="margin:0;color:#64748b;font-size:12px;line-height:1.5;text-align:center;border-top:1px solid rgba(255,255,255,.06);padding-top:18px">
        If you did not request this, you can ignore this email.<br>
        © {{ date('Y') }} Winning Heaven
      </p>
    </div>
  </div>
</body>
</html>
