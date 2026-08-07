<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Risiti #{{ $sale->id }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 14px; color: #111; padding: 24px; max-width: 320px; margin: 0 auto; }
        .center { text-align: center; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .muted { color: #666; font-size: 12px; }
        .divider { border-top: 1px dashed #ccc; margin: 16px 0; }
        .row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .code { font-family: monospace; font-size: 20px; font-weight: bold; letter-spacing: 1px; margin: 12px 0; }
        .footer { margin-top: 20px; font-size: 11px; color: #666; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="center">
        <div class="title">{{ $settings->business_name ?? 'Tech-Sphere Wi-Fi' }}</div>
        <div class="muted">Risiti ya Mauzo — Cash</div>
    </div>

    <div class="divider"></div>

    <div class="row"><span>Tarehe</span><span>{{ $sale->sold_at?->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span>Mauzo #</span><span>{{ $sale->id }}</span></div>
    <div class="row"><span>Package</span><span>{{ $sale->package?->name }}</span></div>
    <div class="row"><span>Muda</span><span>{{ $sale->package?->getDurationLabel() }}</span></div>
    <div class="row"><span>Mteja</span><span>{{ $sale->customer?->name ?? 'Walk-in' }}</span></div>
    <div class="row"><span>Muuzaji</span><span>{{ $sale->admin?->name }}</span></div>

    <div class="divider"></div>

    <div class="center muted">VOUCHER CODE</div>
    <div class="center code">{{ $sale->voucher?->code }}</div>

    <div class="divider"></div>

    <div class="row" style="font-size: 16px; font-weight: bold;">
        <span>JUMLA</span>
        <span>{{ number_format($sale->amount, 0) }} {{ $settings->currency ?? 'TZS' }}</span>
    </div>
    <div class="row"><span>Malipo</span><span>Cash</span></div>

    @if ($sale->notes)
        <div class="divider"></div>
        <div class="muted">{{ $sale->notes }}</div>
    @endif

    <div class="footer center">
        Asante kwa kutumia huduma yetu.<br>
        Tumia voucher code hapo juu kuunganisha Wi-Fi.
    </div>

    <div class="center no-print" style="margin-top: 24px;">
        <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer;">Chapisha</button>
    </div>
</body>
</html>
