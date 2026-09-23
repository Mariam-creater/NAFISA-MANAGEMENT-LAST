<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #FFF5F8; color: #334155; padding: 20px; }
        .card { background-color: #ffffff; border-radius: 12px; padding: 30px; border: 1px solid #FBCFE8; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 15px rgba(244, 114, 182, 0.1); }
        .header { border-bottom: 2px solid #FDF2F8; padding-bottom: 15px; margin-bottom: 20px; }
        .title { color: #0F172A; font-size: 20px; font-weight: 800; }
        .badge { background-color: #FDF2F8; color: #BE185D; padding: 4px 12px; border-radius: 6px; font-weight: bold; font-size: 13px; display: inline-block; }
        .footer { margin-top: 25px; font-size: 11px; color: #94A3B8; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="title">NAFISA HOSTEL</div>
            <div style="font-size: 12px; color: #DB2777; font-weight: 600;">Upcoming Rent Due Notification</div>
        </div>
        <p>Dear <strong>{{ $tenant->full_name }}</strong>,</p>
        <p>This is a polite reminder that your monthly accommodation fee for <strong>{{ $tenant->room ? $tenant->room->block->name . ' - Room ' . $tenant->room->room_number : 'your unit' }}</strong> is due in <strong>{{ $daysRemaining }} day{{ $daysRemaining === 1 ? '' : 's' }}</strong> on <strong>{{ \Carbon\Carbon::parse($tenant->rent_due_date)->format('d F Y') }}</strong>.</p>
        
        <div style="background-color: #FFF5F8; border: 1px solid #FBCFE8; border-radius: 8px; padding: 16px; margin: 20px 0;">
            <div style="font-size: 11px; text-transform: uppercase; color: #BE185D; font-weight: bold;">Amount Due</div>
            <div style="font-size: 22px; font-weight: 800; color: #0F172A; margin-top: 4px;">{{ number_format($amountDue) }} UGX</div>
            <div style="font-size: 12px; color: #64748B; margin-top: 4px;">Due Date: {{ \Carbon\Carbon::parse($tenant->rent_due_date)->format('l, d M Y') }}</div>
        </div>

        <p><strong>Accepted Payment Methods:</strong></p>
        <ul style="color: #64748B; font-size: 13px; line-height: 1.8;">
            <li><strong>Mobile Money (MTN / Airtel):</strong> Transfer to Merchant Code <strong>123456</strong> (Nafisa Hostel)</li>
            <li><strong>Bank Transfer:</strong> Stanbic Bank Uganda, A/C: <strong>9030012345678</strong></li>
            <li><strong>Direct Cash:</strong> Settlement at the Hostel Warden Desk.</li>
        </ul>

        <p>Thank you for ensuring timely settlement to maintain seamless services and uninterrupted comfort.</p>

        <div class="footer">
            Nafisa Hostel Management System &bull; Minimalist Luxury Living<br>
            Plot 18, University Crescent, Kampala, Uganda
        </div>
    </div>
</body>
</html>
