<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #FFF5F8; color: #334155; padding: 20px; }
        .card { background-color: #ffffff; border-radius: 12px; padding: 30px; border: 1px solid #FBCFE8; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 15px rgba(244, 114, 182, 0.1); }
        .header { border-bottom: 2px solid #FDF2F8; padding-bottom: 15px; margin-bottom: 20px; }
        .title { color: #0F172A; font-size: 20px; font-weight: 800; }
        .footer { margin-top: 25px; font-size: 11px; color: #94A3B8; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="title">NAFISA HOSTEL MANAGEMENT</div>
            <div style="font-size: 12px; color: #DB2777; font-weight: 600;">Automated Cloud ZIP Backup</div>
        </div>
        <p>Dear Administrator,</p>
        <p>The weekly automated backup routine for <strong>Nafisa Hostel Management System</strong> has completed successfully. The complete SQLite database and all media assets (tenant photos, ID scans, PDF receipts) have been securely packaged.</p>
        
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr style="border-bottom: 1px solid #F1F5F9;">
                <td style="padding: 8px 0; color: #64748B;">Archive Name:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #0F172A; font-family: monospace;">{{ $zipFilename }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #F1F5F9;">
                <td style="padding: 8px 0; color: #64748B;">Generated At:</td>
                <td style="padding: 8px 0; text-align: right; color: #0F172A;">{{ $timestamp }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #F1F5F9;">
                <td style="padding: 8px 0; color: #64748B;">Archive Size:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #DB2777;">{{ number_format($filesize / 1024, 1) }} KB</td>
            </tr>
            <tr style="border-bottom: 1px solid #F1F5F9;">
                <td style="padding: 8px 0; color: #64748B;">SQLite Integrity:</td>
                <td style="padding: 8px 0; text-align: right; color: #10B981; font-weight: bold;">Verified OK</td>
            </tr>
        </table>

        <p>The ZIP file is attached to this email. Store it in a safe location for disaster recovery.</p>

        <div class="footer">
            Nafisa Hostel Automated Backup Pipeline<br>
            Powered by NativePHP &amp; Laravel 11
        </div>
    </div>
</body>
</html>
