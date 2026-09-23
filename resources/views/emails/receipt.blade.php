<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #FFF5F8; color: #334155; padding: 20px; }
        .card { background-color: #ffffff; border-radius: 12px; padding: 30px; border: 1px solid #FBCFE8; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 15px rgba(244, 114, 182, 0.1); }
        .header { border-bottom: 2px solid #FDF2F8; padding-bottom: 15px; margin-bottom: 20px; }
        .title { color: #0F172A; font-size: 20px; font-weight: 800; }
        .accent { color: #DB2777; font-weight: bold; }
        .badge { background-color: #FDF2F8; color: #BE185D; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 12px; }
        .footer { margin-top: 25px; font-size: 11px; color: #94A3B8; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="title">NAFISA HOSTEL</div>
            <div style="font-size: 12px; color: #DB2777; font-weight: 600;">Payment Acknowledgment &amp; Receipt</div>
        </div>
        <p>Dear <strong>{{ $payment->tenant->full_name }}</strong>,</p>
        <p>Thank you for your payment. We have successfully processed your rent settlement for <strong>{{ $payment->room ? $payment->room->block->name . ' - Room ' . $payment->room->room_number : 'your unit' }}</strong>.</p>
        
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr style="border-bottom: 1px solid #F1F5F9;">
                <td style="padding: 8px 0; color: #64748B;">Receipt Serial:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #0F172A;">{{ $payment->receipt_serial_no }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #F1F5F9;">
                <td style="padding: 8px 0; color: #64748B;">Amount Received:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #DB2777; font-size: 16px;">{{ number_format($payment->amount_received) }} UGX</td>
            </tr>
            <tr style="border-bottom: 1px solid #F1F5F9;">
                <td style="padding: 8px 0; color: #64748B;">Payment Date:</td>
                <td style="padding: 8px 0; text-align: right; color: #0F172A;">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #F1F5F9;">
                <td style="padding: 8px 0; color: #64748B;">Payment Mode:</td>
                <td style="padding: 8px 0; text-align: right; text-transform: uppercase; color: #0F172A;">{{ str_replace('_', ' ', $payment->payment_method) }}</td>
            </tr>
        </table>

        <p>Your official vector PDF receipt is attached to this email for your records.</p>
        <p>If you have any questions, feel free to contact hostel administration.</p>

        <div class="footer">
            Nafisa Hostel Management System &bull; Minimalist Luxury Living<br>
            Plot 18, University Crescent, Kampala, Uganda
        </div>
    </div>
</body>
</html>
