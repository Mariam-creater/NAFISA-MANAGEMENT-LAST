<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment Receipt {$this->payment->receipt_serial_no} - Nafisa Hostel",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.receipt',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->payment->pdf_path && Storage::disk('public')->exists($this->payment->pdf_path)) {
            $attachments[] = Attachment::fromStorageDisk('public', $this->payment->pdf_path)
                ->as("{$this->payment->receipt_serial_no}.pdf")
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
