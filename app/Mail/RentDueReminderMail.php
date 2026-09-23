<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RentDueReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Tenant $tenant, public int $daysRemaining, public int $amountDue)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Rent Payment Reminder - {$this->tenant->room?->room_number} (Nafisa Hostel)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reminder',
        );
    }
}
