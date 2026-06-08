<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $subject ?? 'Notifikasi Sistem Hibah Buku' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f8; color: #333; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #1a3c5e; padding: 28px 32px; }
        .header h1 { color: #fff; font-size: 18px; font-weight: 600; }
        .header p { color: #a8c4e0; font-size: 12px; margin-top: 4px; }
        .body { padding: 32px; }
        .body p { line-height: 1.7; margin-bottom: 16px; font-size: 15px; }
        .btn { display: inline-block; margin: 8px 0 16px; padding: 12px 24px; background: #1a3c5e; color: #fff !important; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; }
        .info-box { background: #f0f5fa; border-left: 4px solid #1a3c5e; border-radius: 4px; padding: 16px 20px; margin: 16px 0; }
        .info-box p { margin: 0; font-size: 14px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .divider { border: none; border-top: 1px solid #eee; margin: 24px 0; }
        .footer { background: #f8f9fa; padding: 20px 32px; text-align: center; font-size: 12px; color: #888; }
        .footer a { color: #1a3c5e; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>📚 AEP UNY – Sistem Hibah Buku</h1>
        <p>Academic Publishing System</p>
    </div>
    <div class="body">
        @yield('content')
    </div>
    <div class="footer">
        <p>Email ini dikirim otomatis oleh sistem. Mohon tidak membalas email ini jika tidak diperlukan klarifikasi lebih lanjut.</p>
        <p style="margin-top:8px">© {{ date('Y') }} AEP UNY – Sistem Penulisan Hibah Buku</p>
    </div>
</div>
</body>
</html>
