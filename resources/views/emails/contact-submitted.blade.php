<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Yeni İletişim Mesajı</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; color: #f8fafc; padding: 24px; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #1e293b; border-radius: 12px; border: 1px solid #334155; padding: 32px; }
        .header { border-bottom: 1px solid #334155; pb-16px; margin-bottom: 24px; }
        .title { color: #ffffff; font-size: 20px; font-weight: bold; margin: 0 0 8px 0; }
        .subtitle { color: #94a3b8; font-size: 14px; margin: 0; }
        .field { margin-bottom: 16px; }
        .label { font-size: 12px; font-weight: 600; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em; margin-bottom: 4px; }
        .value { font-size: 15px; color: #f1f5f9; background-color: #0f172a; padding: 12px; border-radius: 8px; border: 1px solid #334155; white-space: pre-wrap; word-break: break-word; }
        .footer { margin-top: 32px; pt-16px; border-top: 1px solid #334155; font-size: 12px; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">DOST TV — Web Sitesi İletişim Formu</h1>
            <p class="subtitle">Sitenizden yeni bir iletişim mesajı gönderildi.</p>
        </div>

        <div class="field">
            <div class="label">Ad Soyad</div>
            <div class="value">{{ $contactMessage->name }}</div>
        </div>

        <div class="field">
            <div class="label">E-Posta</div>
            <div class="value"><a href="mailto:{{ $contactMessage->email }}" style="color: #38bdf8; text-decoration: none;">{{ $contactMessage->email }}</a></div>
        </div>

        @if(!empty($contactMessage->phone))
            <div class="field">
                <div class="label">Telefon</div>
                <div class="value">{{ $contactMessage->phone }}</div>
            </div>
        @endif

        <div class="field">
            <div class="label">Konu</div>
            <div class="value">{{ $contactMessage->subject }}</div>
        </div>

        <div class="field">
            <div class="label">Mesaj</div>
            <div class="value">{{ $contactMessage->message }}</div>
        </div>

        <div class="field">
            <div class="label">Tarih</div>
            <div class="value">{{ $contactMessage->created_at->format('d.m.Y H:i') }}</div>
        </div>

        <div class="footer">
            Bu e-posta DOST TV web sitesi iletişim formu altyapısı tarafından otomatik olarak oluşturulmuştur.
        </div>
    </div>
</body>
</html>
