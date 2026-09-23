<?php

namespace App\Services;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ReceiptService
{
    /**
     * Generate the next atomic receipt serial number.
     * Format: NFH-{YEAR}-{0001}
     */
    public function generateNextSerialNumber(): string
    {
        $year = date('Y');
        $prefix = "NFH-{$year}-";

        // Query highest serial number for current year
        $latestPayment = Payment::where('receipt_serial_no', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($latestPayment) {
            $parts = explode('-', $latestPayment->receipt_serial_no);
            $lastIndex = (int) end($parts);
            $nextIndex = $lastIndex + 1;
        } else {
            $nextIndex = 1;
        }

        return sprintf('%s%04d', $prefix, $nextIndex);
    }

    /**
     * Generate PDF and save to disk
     */
    public function generateAndSavePdf(Payment $payment): string
    {
        $payment->loadMissing(['tenant', 'room.block']);

        $pdf = Pdf::loadView('receipts.pdf', [
            'payment' => $payment,
        ])->setPaper('a4', 'portrait');

        $year = date('Y', strtotime($payment->payment_date));
        $dir = "receipts/{$year}";
        $filename = "{$payment->receipt_serial_no}.pdf";
        $relativePath = "{$dir}/{$filename}";

        Storage::disk('public')->makeDirectory($dir);
        Storage::disk('public')->put($relativePath, $pdf->output());

        $payment->update(['pdf_path' => $relativePath]);

        return $relativePath;
    }

    /**
     * Get binary PDF stream for download or browser view
     */
    public function getPdfBinary(Payment $payment): string
    {
        if ($payment->pdf_path && Storage::disk('public')->exists($payment->pdf_path)) {
            return Storage::disk('public')->get($payment->pdf_path);
        }

        $this->generateAndSavePdf($payment);
        return Storage::disk('public')->get($payment->pdf_path);
    }
}
