<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Financial Ledger Statement - Nafisa Hostel</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #334155; font-size: 12px; line-height: 1.5; background: #fff; }
        .header-table { width: 100%; border-bottom: 2px solid #FBCFE8; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-title { font-size: 22px; font-weight: 800; color: #0F172A; }
        .statement-badge { background: #FDF2F8; border: 1px solid #F472B6; color: #DB2777; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 11px; }
        .kpi-table { width: 100%; margin-bottom: 25px; border-collapse: separate; border-spacing: 10px 0; }
        .kpi-cell { background: #FFF5F8; border: 1px solid #FBCFE8; border-radius: 8px; padding: 12px; text-align: center; }
        .kpi-label { font-size: 10px; text-transform: uppercase; color: #BE185D; font-weight: bold; }
        .kpi-val { font-size: 16px; font-weight: 800; color: #0F172A; margin-top: 4px; font-family: monospace; }
        .ledger-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .ledger-table th { background: #FDF2F8; color: #BE185D; text-align: left; padding: 8px 10px; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #FBCFE8; }
        .ledger-table td { padding: 8px 10px; border-bottom: 1px solid #F1F5F9; font-size: 11px; }
        .footer { margin-top: 30px; border-top: 1px dashed #CBD5E1; padding-top: 15px; font-size: 11px; color: #94A3B8; text-align: center; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <div class="logo-title">NAFISA HOSTEL</div>
                <div style="font-size: 11px; color: #DB2777; font-weight: bold; letter-spacing: 1px;">OFFICIAL FINANCIAL PERFORMANCE STATEMENT</div>
                <div style="font-size: 11px; color: #64748B; margin-top: 4px;">Reporting Window: <strong>{{ $dateRangeLabel }}</strong></div>
            </td>
            <td style="text-align: right;">
                <span class="statement-badge">EXECUTIVE LEDGER</span>
                <div style="font-size: 11px; color: #64748B; margin-top: 4px;">Generated: {{ date('d M Y, H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="kpi-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="kpi-cell" style="width: 25%;">
                <div class="kpi-label">Total Invoiced</div>
                <div class="kpi-val">{{ number_format($stats['total_invoiced']) }} UGX</div>
            </td>
            <td class="kpi-cell" style="width: 25%;">
                <div class="kpi-label">Total Collected</div>
                <div class="kpi-val" style="color: #DB2777;">{{ number_format($stats['total_collected']) }} UGX</div>
            </td>
            <td class="kpi-cell" style="width: 25%;">
                <div class="kpi-label">Taxes / Levies</div>
                <div class="kpi-val">{{ number_format($stats['total_tax']) }} UGX</div>
            </td>
            <td class="kpi-cell" style="width: 25%;">
                <div class="kpi-label">Outstanding Deficit</div>
                <div class="kpi-val" style="color: #E11D48;">{{ number_format($stats['outstanding_deficit']) }} UGX</div>
            </td>
        </tr>
    </table>

    <div style="font-size: 13px; font-weight: bold; color: #0F172A; margin-bottom: 8px;">Transaction Breakdown ({{ $payments->count() }} Records)</div>
    <table class="ledger-table">
        <thead>
            <tr>
                <th>Serial</th>
                <th>Date</th>
                <th>Resident</th>
                <th>Unit</th>
                <th>Method</th>
                <th style="text-align: right;">Paid (UGX)</th>
                <th style="text-align: right;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $p)
            <tr>
                <td style="font-family: monospace; font-weight: bold; color: #0F172A;">{{ $p->receipt_serial_no }}</td>
                <td>{{ \Carbon\Carbon::parse($p->payment_date)->format('d M Y') }}</td>
                <td>{{ $p->tenant->full_name }}</td>
                <td>{{ $p->room ? $p->room->block->name . ' - ' . $p->room->room_number : 'N/A' }}</td>
                <td style="text-transform: uppercase;">{{ str_replace('_', ' ', $p->payment_method) }}</td>
                <td style="text-align: right; font-weight: bold; color: #DB2777; font-family: monospace;">{{ number_format($p->amount_received) }} UGX</td>
                <td style="text-align: right; font-family: monospace;">
                    @if(($p->total_due - $p->amount_received) > 0)
                        <span style="color: #E11D48; font-weight: bold;">{{ number_format($p->total_due - $p->amount_received) }} UGX</span>
                    @else
                        <span style="color: #10B981; font-weight: 600;">Cleared</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #94A3B8; padding: 20px;">No transactions recorded in this window.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Nafisa Hostel Management System &bull; Standalone Executive Ledger &bull; Kampala, Uganda
    </div>
</body>
</html>
