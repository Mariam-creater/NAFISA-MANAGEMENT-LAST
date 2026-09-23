<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tenant Dossier - {{ $tenant->full_name }}</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #334155; font-size: 13px; line-height: 1.5; background: #fff; }
        .header { border-bottom: 2px solid #FBCFE8; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-title { font-size: 22px; font-weight: 800; color: #0F172A; }
        .badge { background: #FDF2F8; border: 1px solid #F472B6; color: #DB2777; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; }
        .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #BE185D; margin-top: 25px; margin-bottom: 10px; border-bottom: 1px solid #FCE7F3; padding-bottom: 4px; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 6px 4px; vertical-align: top; }
        .label { color: #64748B; font-size: 11px; text-transform: uppercase; }
        .val { font-weight: 600; color: #0F172A; }
        .ledger-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .ledger-table th { background: #FDF2F8; color: #BE185D; text-align: left; padding: 8px 10px; font-size: 11px; border-bottom: 1px solid #FBCFE8; }
        .ledger-table td { padding: 8px 10px; border-bottom: 1px solid #F1F5F9; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="logo-title">NAFISA HOSTEL</div>
                    <div style="font-size: 11px; color: #DB2777; font-weight: bold; letter-spacing: 1px;">HISTORICAL TENANT DOSSIER</div>
                </td>
                <td style="text-align: right;">
                    <span class="badge">{{ $tenant->deleted_at ? 'ARCHIVED RESIDENT' : 'ACTIVE RESIDENT' }}</span>
                    <div style="font-size: 11px; color: #64748B; margin-top: 4px;">Record ID: NFH-T-{{ $tenant->id }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Personal &amp; Contact Particulars</div>
    <table class="info-table">
        <tr>
            <td style="width: 25%;"><span class="label">Full Name:</span><br><span class="val">{{ $tenant->full_name }}</span></td>
            <td style="width: 25%;"><span class="label">Age:</span><br><span class="val">{{ $tenant->age }} years old</span></td>
            <td style="width: 25%;"><span class="label">Occupation:</span><br><span class="val">{{ ucfirst($tenant->occupation) }}</span></td>
            <td style="width: 25%;"><span class="label">WhatsApp / Tel:</span><br><span class="val">{{ $tenant->whatsapp_number }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Email Address:</span><br><span class="val">{{ $tenant->email }}</span></td>
            <td><span class="label">Check-In Date:</span><br><span class="val">{{ \Carbon\Carbon::parse($tenant->check_in_date)->format('d M Y') }}</span></td>
            <td><span class="label">Emergency Contact:</span><br><span class="val">{{ $tenant->emergency_name }} ({{ $tenant->emergency_relationship }})</span></td>
            <td><span class="label">Emergency Phone:</span><br><span class="val">{{ $tenant->emergency_phone }}</span></td>
        </tr>
    </table>

    <div class="section-title">Complete Historical Financial Ledger</div>
    @if($payments->isEmpty())
        <p style="color: #94A3B8; font-style: italic;">No payment transactions recorded for this tenant.</p>
    @else
        <table class="ledger-table">
            <thead>
                <tr>
                    <th>Serial No</th>
                    <th>Date</th>
                    <th>Room Unit</th>
                    <th>Payment Method</th>
                    <th style="text-align: right;">Amount (UGX)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $p)
                <tr>
                    <td style="font-family: monospace; font-weight: bold; color: #0F172A;">{{ $p->receipt_serial_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($p->payment_date)->format('d M Y') }}</td>
                    <td>{{ $p->room ? $p->room->block->name . ' - Rm ' . $p->room->room_number : 'N/A' }}</td>
                    <td style="text-transform: uppercase;">{{ str_replace('_', ' ', $p->payment_method) }}</td>
                    <td style="text-align: right; font-weight: bold; color: #DB2777; font-family: monospace;">{{ number_format($p->amount_received) }} UGX</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="text-align: right; margin-top: 15px; font-weight: bold; font-size: 14px; color: #0F172A;">
            Cumulative Lifetime Settlement: <span style="color: #DB2777;">{{ number_format($payments->sum('amount_received')) }} UGX</span>
        </div>
    @endif

    <div style="margin-top: 40px; border-top: 1px dashed #CBD5E1; padding-top: 15px; font-size: 11px; color: #94A3B8; text-align: center;">
        Printed from Nafisa Hostel Management Desktop System &bull; Certified Authentic Ledger
    </div>
</body>
</html>
