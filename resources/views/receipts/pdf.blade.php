<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Receipt {{ $payment->receipt_serial_no }}</title>
    <style>
        @page {
            margin: 28px 32px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1E293B;
            margin: 0;
            padding: 0;
            font-size: 13px;
            background-color: #ffffff;
        }
        .header-wrap {
            width: 100%;
            border-bottom: 2px solid #FBCFE8;
            padding-bottom: 18px;
            margin-bottom: 22px;
        }
        .title {
            font-size: 24px;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .subtitle {
            font-size: 11px;
            color: #DB2777;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 3px;
        }
        .receipt-pill {
            display: inline-block;
            background-color: #FDF2F8;
            border: 1px solid #F472B6;
            color: #DB2777;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
        }
        .serial-no {
            font-family: 'Courier New', Courier, monospace;
            font-size: 16px;
            font-weight: 800;
            color: #0F172A;
            margin-top: 6px;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 24px;
            border-collapse: separate;
            border-spacing: 0;
        }
        .info-card {
            background-color: #FFF5F8;
            border: 1px solid #FCE7F3;
            border-radius: 8px;
            padding: 12px 16px;
            vertical-align: top;
        }
        .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748B;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .value {
            font-size: 14px;
            font-weight: 700;
            color: #0F172A;
        }
        .sub-value {
            font-size: 12px;
            color: #475569;
            margin-top: 2px;
        }
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .breakdown-table th {
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
        .breakdown-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #F1F5F9;
            color: #334155;
            font-size: 13px;
        }
        .summary-wrap {
            width: 100%;
            margin-bottom: 25px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-label {
            text-align: right;
            padding: 6px 12px;
            color: #64748B;
            font-size: 13px;
        }
        .summary-val {
            text-align: right;
            padding: 6px 12px;
            font-weight: 700;
            color: #0F172A;
            font-size: 14px;
            font-family: 'Courier New', Courier, monospace;
        }
        .paid-label {
            text-align: right;
            padding: 8px 12px;
            color: #0F172A;
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #E2E8F0;
        }
        .paid-val {
            text-align: right;
            padding: 8px 12px;
            font-size: 17px;
            font-weight: 800;
            color: #DB2777;
            font-family: 'Courier New', Courier, monospace;
            border-top: 1px solid #E2E8F0;
        }
        .balance-label {
            text-align: right;
            padding: 10px 12px;
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #FBCFE8;
        }
        .balance-val {
            text-align: right;
            padding: 10px 12px;
            font-size: 16px;
            font-weight: 800;
            font-family: 'Courier New', Courier, monospace;
            border-top: 2px solid #FBCFE8;
        }
        .balance-due {
            color: #E11D48;
        }
        .balance-cleared {
            color: #10B981;
        }
        .footer {
            margin-top: 35px;
            border-top: 1px dashed #FBCFE8;
            padding-top: 14px;
            text-align: center;
            font-size: 11px;
            color: #94A3B8;
        }
    </style>
</head>
<body>
    <!-- Clean Luxury Header -->
    <table class="header-wrap" cellpadding="0" cellspacing="0">
        <tr>
            <td style="vertical-align: middle;">
                <div class="title">NAFISA HOSTEL</div>
                <div class="subtitle">Official Rent Receipt</div>
            </td>
            <td style="vertical-align: middle; text-align: right;">
                <div class="receipt-pill">RECEIPT</div>
                <div class="serial-no">{{ $payment->receipt_serial_no }}</div>
                <div style="font-size: 11px; color: #64748B; margin-top: 3px;">
                    Date Paid: <strong>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d F Y') }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- Clean Tenant & Room Particulars (2 Columns) -->
    <table class="info-grid" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 48%;" class="info-card">
                <div class="label">Resident (Tenant)</div>
                <div class="value">{{ $payment->tenant->full_name }}</div>
                <div class="sub-value">Phone: {{ $payment->tenant->whatsapp_number }}</div>
                @if($payment->tenant->email)
                    <div class="sub-value">Email: {{ $payment->tenant->email }}</div>
                @endif
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%;" class="info-card">
                <div class="label">Unit &amp; Payment Details</div>
                <div class="value">
                    {{ $payment->room ? $payment->room->block->name . ' — Room ' . $payment->room->room_number : 'Assigned Unit' }}
                </div>
                <div class="sub-value">
                    Floor: {{ $payment->room ? 'Floor ' . $payment->room->floor_number : 'N/A' }}
                    &bull; Method: <strong style="text-transform: uppercase; color: #DB2777;">{{ str_replace('_', ' ', $payment->payment_method) }}</strong>
                </div>
                @if($payment->notes)
                    <div class="sub-value" style="color: #DB2777;">Ref: {{ $payment->notes }}</div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Itemized Breakdown -->
    <table class="breakdown-table">
        <thead>
            <tr>
                <th style="width: 70%;">Description</th>
                <th style="width: 30%; text-align: right;">Amount (UGX)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong style="color: #0F172A;">Monthly Accommodation Charge</strong>
                    <div style="color: #64748B; font-size: 11px; margin-top: 2px;">
                        {{ $payment->room ? $payment->room->block->name : 'Unit' }} Rent Settlement
                    </div>
                </td>
                <td style="text-align: right; font-weight: bold; font-family: 'Courier New', Courier, monospace;">
                    {{ number_format($payment->base_rent_charged) }} UGX
                </td>
            </tr>
            @if($payment->tax_amount > 0)
            <tr>
                <td>
                    <strong style="color: #0F172A;">Tax / Surcharge</strong>
                </td>
                <td style="text-align: right; font-weight: bold; font-family: 'Courier New', Courier, monospace;">
                    {{ number_format($payment->tax_amount) }} UGX
                </td>
            </tr>
            @endif

            @php
                $previousArrears = max(0, $payment->total_due - ($payment->base_rent_charged + $payment->tax_amount));
            @endphp
            @if($previousArrears > 0)
            <tr>
                <td>
                    <strong style="color: #0F172A;">Previous Outstanding Balance</strong>
                    <div style="color: #64748B; font-size: 11px; margin-top: 2px;">
                        Carried over from prior billing cycle
                    </div>
                </td>
                <td style="text-align: right; font-weight: bold; font-family: 'Courier New', Courier, monospace; color: #E11D48;">
                    {{ number_format($previousArrears) }} UGX
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- Financial Summary Box: Total, Paid, Balance Remaining -->
    @php
        $balance = max(0, $payment->total_due - $payment->amount_received);
    @endphp
    <table class="summary-wrap" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 45%; vertical-align: top;">
                <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px 16px;">
                    <div style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Payment Status</div>
                    <div style="font-size: 14px; font-weight: 800; margin-top: 3px; color: {{ $balance > 0 ? '#E11D48' : '#10B981' }};">
                        {{ $balance > 0 ? 'PARTIAL PAYMENT RECORDED' : 'FULL PAYMENT SETTLED' }}
                    </div>
                    <div style="font-size: 11px; color: #64748B; margin-top: 4px;">
                        Date Paid: {{ \Carbon\Carbon::parse($payment->payment_date)->format('d F Y') }}
                    </div>
                </div>
            </td>
            <td style="width: 55%; vertical-align: top;">
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Monthly Rent:</td>
                        <td class="summary-val">{{ number_format($payment->base_rent_charged) }} UGX</td>
                    </tr>
                    @if($payment->tax_amount > 0)
                    <tr>
                        <td class="summary-label">Tax / Additional Fee:</td>
                        <td class="summary-val" style="color: #BE185D;">{{ number_format($payment->tax_amount) }} UGX</td>
                    </tr>
                    @endif
                    @if($previousArrears > 0)
                    <tr>
                        <td class="summary-label" style="color: #B45309;">Previous Balance:</td>
                        <td class="summary-val" style="color: #B45309;">{{ number_format($previousArrears) }} UGX</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="summary-label" style="font-weight: bold; color: #0F172A; border-top: 1px solid #E2E8F0; padding-top: 8px;">Total Amount Due:</td>
                        <td class="summary-val" style="font-weight: 800; border-top: 1px solid #E2E8F0; padding-top: 8px;">{{ number_format($payment->total_due) }} UGX</td>
                    </tr>
                    <tr>
                        <td class="paid-label">Amount Paid:</td>
                        <td class="paid-val">{{ number_format($payment->amount_received) }} UGX</td>
                    </tr>
                    <tr>
                        <td class="balance-label {{ $balance > 0 ? 'balance-due' : 'balance-cleared' }}" style="{{ $balance > 0 ? 'background-color: #FFF1F2; border-radius: 4px 0 0 4px;' : '' }}">
                            Balance Remaining:
                        </td>
                        <td class="balance-val {{ $balance > 0 ? 'balance-due' : 'balance-cleared' }}" style="{{ $balance > 0 ? 'background-color: #FFF1F2; border-radius: 0 4px 4px 0;' : '' }}">
                            @if($balance > 0)
                                {{ number_format($balance) }} UGX
                            @else
                                0 UGX (CLEARED)
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Clean, Simple Footer -->
    <div class="footer">
        Nafisa Hostel &bull; Thank you for your payment!
    </div>
</body>
</html>
