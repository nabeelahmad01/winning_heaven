<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#030a12;color:#ecf4ff;font-family:Helvetica,Arial,sans-serif">
  <div style="max-width:560px;margin:40px auto;background:#0a1522;border:1px solid rgba(62,224,178,.4);border-radius:18px;overflow:hidden">
    <div style="padding:22px 24px;border-bottom:1px solid rgba(76,201,240,.15);background:linear-gradient(135deg,#0c1a28,#061018)">
      <div style="font-size:18px;font-weight:900;letter-spacing:1px">
        <span style="color:#3ee0b2">WINNING</span> <span style="color:#ffe2a8">HEAVEN</span>
      </div>
      <div style="color:#9bb0c7;font-size:12px;margin-top:4px">Exclusive promotion</div>
    </div>
    <div style="padding:28px 24px">
      <h1 style="margin:0 0 12px;font-size:22px;color:#fff">{{ $title }}</h1>
      <p style="margin:0;color:#c5d4e6;font-size:15px;line-height:1.65;white-space:pre-wrap">{{ $message }}</p>
      @if(!empty($image))
        <div style="margin-top:18px;border-radius:12px;overflow:hidden">
          <img src="{{ $image }}" alt="" style="width:100%;display:block;max-height:280px;object-fit:cover">
        </div>
      @endif
      <p style="margin:22px 0 0;text-align:center">
        <a href="{{ $lobbyUrl }}" style="display:inline-block;padding:12px 22px;border-radius:12px;background:linear-gradient(135deg,#4cc9f0,#3ee0b2);color:#041018;font-weight:800;text-decoration:none">Open Lobby</a>
      </p>
    </div>
  </div>
</body>
</html>
