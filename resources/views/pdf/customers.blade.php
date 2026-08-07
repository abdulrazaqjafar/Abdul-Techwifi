<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Report - {{ $settings->business_name ?? 'Tech-Sphere' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e3a5f; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 24px; border-bottom: 3px solid #16a34a; padding-bottom: 16px; }
        .header h1 { color: #1e3a5f; font-size: 20px; margin: 0 0 4px; }
        .header h2 { color: #16a34a; font-size: 14px; margin: 0; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #1e3a5f; color: white; padding: 8px 6px; text-align: left; font-size: 10px; }
        td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8fafc; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $settings->business_name ?? 'Tech-Sphere Wi-fi Billing System' }}</h1>
        <h2>Customer Report</h2>
        <p>Generated on {{ now()->format('F d, Y \a\t H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Vouchers</th>
                <th>Sales</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->phone ?? '—' }}</td>
                    <td>{{ $customer->email ?? '—' }}</td>
                    <td>{{ $customer->vouchers_count ?? 0 }}</td>
                    <td>{{ $customer->sales_count ?? 0 }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No customers found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">{{ $settings->business_name ?? 'Tech-Sphere' }} — Confidential</div>
</body>
</html>
