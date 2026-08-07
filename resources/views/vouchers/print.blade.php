@php
    $settings = \App\Models\BusinessSetting::getSettings();
    $currency = $settings->currency ?? 'USD';
    $businessName = $settings->business_name ?? 'Tech-Sphere Wi-fi';
    $logoUrl = $settings->business_logo ? asset('storage/' . $settings->business_logo) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Vouchers - {{ $businessName }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f1f5f9; color: #1e3a5f; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; justify-content: center; gap: 12px; padding: 16px; background: #1e3a5f; }
        .toolbar button { padding: 10px 24px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .btn-print { background: #16a34a; color: white; }
        .btn-close { background: white; color: #1e3a5f; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; padding: 24px; max-width: 1400px; margin: 0 auto; }
        .voucher { background: white; border-radius: 16px; border: 2px solid #16a34a; overflow: hidden; page-break-inside: avoid; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .voucher-header { background: linear-gradient(135deg, #1e3a5f, #334e68); color: white; padding: 16px 20px; text-align: center; }
        .voucher-header img { max-height: 48px; margin-bottom: 8px; }
        .voucher-header h1 { font-size: 16px; font-weight: 700; }
        .voucher-header p { font-size: 11px; opacity: 0.85; margin-top: 4px; }
        .voucher-body { padding: 20px; text-align: center; }
        .code { font-size: 22px; font-weight: 800; letter-spacing: 2px; color: #16a34a; font-family: 'Courier New', monospace; margin: 12px 0; padding: 12px; background: #f0fdf4; border-radius: 10px; border: 1px dashed #16a34a; }
        .details { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; text-align: left; margin: 16px 0; font-size: 12px; }
        .detail-item { padding: 8px; background: #f8fafc; border-radius: 8px; }
        .detail-item label { display: block; font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 600; }
        .detail-item span { font-weight: 700; color: #1e3a5f; }
        .qr { margin: 12px auto; }
        .qr img { width: 100px; height: 100px; border-radius: 8px; }
        .instructions { font-size: 11px; color: #64748b; line-height: 1.6; padding: 12px 20px 16px; border-top: 1px solid #e2e8f0; text-align: left; }
        .instructions strong { color: #1e3a5f; }
        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .grid { grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 0; }
            .voucher { box-shadow: none; break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()">Print Vouchers</button>
        <button class="btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="grid">
        @forelse ($vouchers as $voucher)
            <div class="voucher">
                <div class="voucher-header">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $businessName }}">
                    @endif
                    <h1>{{ $businessName }}</h1>
                    <p>Wi-Fi Hotspot Voucher</p>
                </div>
                <div class="voucher-body">
                    <div class="code">{{ $voucher->code }}</div>
                    <div class="details">
                        <div class="detail-item">
                            <label>Package</label>
                            <span>{{ $voucher->package?->name ?? 'N/A' }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Price</label>
                            <span>{{ number_format($voucher->package?->price ?? 0, 2) }} {{ $currency }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Validity</label>
                            <span>{{ $voucher->package?->getDurationLabel() ?? 'N/A' }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Username</label>
                            <span>{{ $voucher->mikrotik_username ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="qr">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($voucher->code) }}" alt="QR Code">
                    </div>
                </div>
                <div class="instructions">
                    <strong>How to connect:</strong><br>
                    1. Connect to our Wi-Fi network<br>
                    2. Open your browser — the login page will appear<br>
                    3. Enter voucher code: <strong>{{ $voucher->code }}</strong><br>
                    4. Enjoy your internet access!
                </div>
            </div>
        @empty
            <p style="grid-column: 1/-1; text-align: center; padding: 48px;">No printable vouchers found.</p>
        @endforelse
    </div>
</body>
</html>
