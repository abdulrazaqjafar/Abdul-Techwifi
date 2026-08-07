<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vouchers - {{ $settings->business_name ?? 'Tech-Sphere' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e3a5f; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #16a34a; padding-bottom: 12px; }
        .header h1 { color: #1e3a5f; font-size: 18px; margin: 0; }
        .header p { color: #64748b; margin: 4px 0 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #1e3a5f; color: white; padding: 8px 6px; text-align: left; font-size: 10px; }
        td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8fafc; }
        .code { font-family: DejaVu Sans Mono, monospace; font-weight: bold; color: #16a34a; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #94a3b8; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-unused { background: #dcfce7; color: #166534; }
        .badge-used { background: #dbeafe; color: #1e40af; }
        .badge-expired { background: #fef3c7; color: #92400e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $settings->business_name ?? 'Tech-Sphere Wi-fi Billing System' }}</h1>
        <p>Voucher Export — Generated {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Code</th>
                <th>Package</th>
                <th>Price</th>
                <th>Duration</th>
                <th>Status</th>
                <th>Username</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vouchers as $index => $voucher)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="code">{{ $voucher->code }}</td>
                    <td>{{ $voucher->package?->name ?? 'N/A' }}</td>
                    <td>{{ number_format($voucher->package?->price ?? 0, 2) }} {{ $settings->currency ?? 'USD' }}</td>
                    <td>{{ $voucher->package?->getDurationLabel() ?? 'N/A' }}</td>
                    <td><span class="badge badge-{{ $voucher->status }}">{{ ucfirst($voucher->status) }}</span></td>
                    <td>{{ $voucher->mikrotik_username ?? '—' }}</td>
                    <td>{{ $voucher->created_at?->format('M d, Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Total: {{ $vouchers->count() }} vouchers | {{ $settings->business_name ?? 'Tech-Sphere' }} @if($settings->phone)| {{ $settings->phone }}@endif
    </div>
</body>
</html>
