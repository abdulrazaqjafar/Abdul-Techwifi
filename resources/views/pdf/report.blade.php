<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Report' }} - {{ $settings->business_name ?? 'Tech-Sphere' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e3a5f; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 24px; border-bottom: 3px solid #16a34a; padding-bottom: 16px; }
        .header h1 { color: #1e3a5f; font-size: 20px; margin: 0 0 4px; }
        .header h2 { color: #16a34a; font-size: 14px; margin: 0; font-weight: normal; }
        .header p { color: #64748b; margin: 8px 0 0; font-size: 10px; }
        .meta { margin-bottom: 16px; }
        .meta table { width: 100%; }
        .meta td { padding: 4px 8px; }
        .meta .label { font-weight: bold; color: #64748b; width: 120px; }
        .summary { display: table; width: 100%; margin-bottom: 20px; }
        .summary-box { display: table-cell; width: 33.33%; text-align: center; padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; }
        .summary-box .value { font-size: 18px; font-weight: bold; color: #16a34a; }
        .summary-box .label { font-size: 9px; color: #64748b; text-transform: uppercase; margin-top: 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.data th { background: #1e3a5f; color: white; padding: 8px 6px; text-align: left; font-size: 10px; }
        table.data td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; }
        table.data tr:nth-child(even) { background: #f8fafc; }
        .section-title { color: #1e3a5f; font-size: 13px; font-weight: bold; margin: 20px 0 8px; border-left: 3px solid #16a34a; padding-left: 8px; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 12px; }
        .text-right { text-align: right; }
        .text-green { color: #16a34a; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $settings->business_name ?? 'Tech-Sphere Wi-fi Billing System' }}</h1>
        <h2>{{ $title ?? 'Business Report' }}</h2>
        <p>Generated on {{ now()->format('F d, Y \a\t H:i') }} @if(isset($filters['date_from']))| Period: {{ $filters['date_from'] }} to {{ $filters['date_to'] ?? now()->toDateString() }}@endif</p>
    </div>

    @if (isset($report['summary']))
        <div class="summary">
            <div class="summary-box">
                <div class="value">{{ number_format($report['summary']['total_sales'] ?? 0) }}</div>
                <div class="label">Total Sales</div>
            </div>
            <div class="summary-box">
                <div class="value">{{ number_format($report['summary']['total_revenue'] ?? 0, 2) }}</div>
                <div class="label">Total Revenue ({{ $settings->currency ?? 'USD' }})</div>
            </div>
            <div class="summary-box">
                <div class="value">{{ number_format($report['summary']['average_sale'] ?? 0, 2) }}</div>
                <div class="label">Average Sale</div>
            </div>
        </div>
    @endif

    @if (isset($report['daily_breakdown']) && count($report['daily_breakdown']))
        <div class="section-title">Daily Breakdown</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Sales Count</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['daily_breakdown'] as $row)
                    <tr>
                        <td>{{ $row->date }}</td>
                        <td>{{ $row->count }}</td>
                        <td class="text-right text-green">{{ number_format($row->revenue, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (isset($report['by_payment_method']) && count($report['by_payment_method']))
        <div class="section-title">By Payment Method</div>
        <table class="data">
            <thead><tr><th>Method</th><th>Count</th><th class="text-right">Revenue</th></tr></thead>
            <tbody>
                @foreach ($report['by_payment_method'] as $row)
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $row->payment_method)) }}</td>
                        <td>{{ $row->count }}</td>
                        <td class="text-right text-green">{{ number_format($row->revenue, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (isset($report['by_package']) && count($report['by_package']))
        <div class="section-title">By Package</div>
        <table class="data">
            <thead><tr><th>Package</th><th>Count</th><th class="text-right">Revenue</th></tr></thead>
            <tbody>
                @foreach ($report['by_package'] as $row)
                    <tr>
                        <td>{{ $row->package?->name ?? 'Unknown' }}</td>
                        <td>{{ $row->count }}</td>
                        <td class="text-right text-green">{{ number_format($row->revenue, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (isset($rows) && count($rows))
        <div class="section-title">{{ $sectionTitle ?? 'Details' }}</div>
        {{ $rows }}
    @endif

    <div class="footer">
        {{ $settings->business_name ?? 'Tech-Sphere' }}
        @if($settings->phone ?? false) | {{ $settings->phone }} @endif
        @if($settings->address ?? false) | {{ $settings->address }} @endif
        <br>Confidential — For internal use only
    </div>
</body>
</html>
