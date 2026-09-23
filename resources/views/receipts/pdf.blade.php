<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Receipt - {{ $payment->receipt_serial_no }}</title>
    <style>
        @page {
            margin: 25px 30px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #334155;
            margin: 0;
            padding: 0;
            font-size: 13px;
            line-height: 1.5;
            background-color: #ffffff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #FBCFE8;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .logo-title {
            font-size: 24px;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .logo-tagline {
            font-size: 11px;
            color: #DB2777;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin-top: 4px;
        }
        .receipt-badge {
            background-color: #FDF2F8;
            border: 1px solid #F472B6;
            color: #DB2777;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 6px;
            display: inline-block;
            text-align: right;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .meta-col {
            width: 50%;
            vertical-align: top;
        }
        .meta-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #94A3B8;
            font-weight: bold;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }
        .meta-value {
            font-size: 13px;
            font-weight: 600;
            color: #0F172A;
        }
        .meta-sub {
            font-size: 12px;
            color: #64748B;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: #FDF2F8;
            color: #BE185D;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid #FBCFE8;
        }
        .items-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #F1F5F9;
            color: #334155;
            font-size: 12px;
        }
        .total-box-table {
            width: 100%;
            margin-bottom: 35px;
        }
        .total-row-label {
            text-align: right;
            padding: 6px 14px;
            color: #64748B;
            font-size: 12px;
        }
        .total-row-val {
            text-align: right;
            padding: 6px 14px;
            font-weight: 600;
            color: #0F172A;
            font-size: 13px;
            font-family: 'Courier New', Courier, monospace;
        }
        .grand-total-label {
            text-align: right;
            padding: 10px 14px;
            color: #0F172A;
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #FBCFE8;
        }
        .grand-total-val {
            text-align: right;
            padding: 10px 14px;
            font-size: 16px;
            font-weight: bold;
            color: #DB2777;
            font-family: 'Courier New', Courier, monospace;
            border-top: 2px solid #FBCFE8;
        }
        .footer-note {
            background-color: #FFF5F8;
            border: 1px dashed #F472B6;
            border-radius: 8px;
            padding: 14px 18px;
            font-size: 11px;
            color: #64748B;
            line-height: 1.6;
        }
        .qr-placeholder {
            width: 80px;
            height: 80px;
            border: 1px solid #FBCFE8;
            background: #FFFFFF;
            text-align: center;
            line-height: 80px;
            font-size: 9px;
            color: #DB2777;
            font-weight: bold;
            border-radius: 6px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="vertical-align: top;">
                <div class="logo-title">NAFISA HOSTEL</div>
                <div class="logo-tagline">Minimalist Luxury Living & Accommodation</div>
                <div style="font-size: 11px; color: #64748B; margin-top: 6px;">
                    Plot 18, University Crescent, Kampala, Uganda<br>
                    Tel: +256 700 000 000 | Email: accounts@nafisahostel.com
                </div>
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div class="receipt-badge">OFFICIAL RECEIPT</div>
                <div style="font-family: 'Courier New', Courier, monospace; font-size: 15px; font-weight: bold; color: #0F172A; margin-top: 8px;">
                    {{ $payment->receipt_serial_no }}
                </div>
                <div style="font-size: 11px; color: #64748B; margin-top: 4px;">
                    Issue Date: {{ \Carbon\Carbon::parse($payment->payment_date)->format('d F Y') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="meta-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="meta-col">
                <div class="meta-label">Billed To (Tenant)</div>
                <div class="meta-value">{{ $payment->tenant->full_name }}</div>
                <div class="meta-sub">Tel: {{ $payment->tenant->whatsapp_number }}</div>
                <div class="meta-sub">Email: {{ $payment->tenant->email }}</div>
                <div class="meta-sub">Occupation: {{ ucfirst($payment->tenant->occupation) }}</div>
            </td>
            <td class="meta-col" style="padding-left: 30px;">
                <div class="meta-label">Accommodation Allocation</div>
                <div class="meta-value">{{ $payment->room ? $payment->room->block->name . ' — Room ' . $payment->room->room_number : 'Assigned Unit' }}</div>
                <div class="meta-sub">Floor: {{ $payment->room ? 'Floor ' . $payment->room->floor_number : 'N/A' }}</div>
                <div class="meta-sub">Payment Mode: <strong style="color: #DB2777; text-transform: uppercase;">{{ str_replace('_', ' ', $payment->payment_method) }}</strong></div>
                <div class="meta-sub">Status: <span style="color: #10B981; font-weight: bold;">PAID &amp; CONFIRMED</span></div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 55%;">Description</th>
                <th style="width: 15%; text-align: center;">Cycle</th>
                <th style="width: 30%; text-align: right;">Amount (UGX)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong style="color: #0F172A;">Hostel Room Accommodation Charge</strong><br>
                    <span style="color: #64748B; font-size: 11px;">
                        {{ $payment->room ? $payment->room->block->description : 'Monthly lease accommodation' }}
                    </span>
                    @if($payment->notes)
                        <br><span style="color: #DB2777; font-size: 11px;">Note: {{ $payment->notes }}</span>
                    @endif
                </td>
                <td style="text-align: center; color: #64748B;">1 Month</td>
                <td style="text-align: right; font-weight: 600; font-family: 'Courier New', Courier, monospace;">
                    {{ number_format($payment->base_rent_charged) }} UGX
                </td>
            </tr>
            @if($payment->tax_amount > 0)
            <tr>
                <td>
                    <strong style="color: #0F172A;">Applicable Government Tax / Levy</strong>
                </td>
                <td style="text-align: center; color: #64748B;">—</td>
                <td style="text-align: right; font-weight: 600; font-family: 'Courier New', Courier, monospace;">
                    {{ number_format($payment->tax_amount) }} UGX
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <table class="total-box-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align: middle;">
                            <div class="qr-placeholder">
                                <span style="display:block; line-height: 1.2; padding-top: 25px;">VERIFIED<br>OFFICIAL</span>
                            </div>
                        </td>
                        <td style="padding-left: 14px; vertical-align: middle;">
                            <div style="font-size: 11px; font-weight: bold; color: #0F172A;">Tamper-Proof Verification</div>
                            <div style="font-size: 10px; color: #64748B; margin-top: 2px;">
                                Generated by Nafisa Desktop System.<br>
                                Verification Hash: {{ substr(md5($payment->receipt_serial_no . $payment->amount_received), 0, 16) }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <table style="width: 100%;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="total-row-label">Base Rate:</td>
                        <td class="total-row-val">{{ number_format($payment->base_rent_charged) }} UGX</td>
                    </tr>
                    @if($payment->tax_amount > 0)
                    <tr>
                        <td class="total-row-label">Tax Total:</td>
                        <td class="total-row-val">{{ number_format($payment->tax_amount) }} UGX</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="grand-total-label">Total Amount Paid:</td>
                        <td class="grand-total-val">{{ number_format($payment->amount_received) }} UGX</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        <strong>Terms &amp; Tenancy Advisory:</strong> All rent payments must be settled in advance of each monthly cycle. This electronic receipt serves as official legal acknowledgment of payment received by Nafisa Hostel Management. Thank you for your continued residence!
    </div>
</body>
</html>
