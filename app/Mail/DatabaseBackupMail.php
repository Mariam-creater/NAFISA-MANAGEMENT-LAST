<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class DatabaseBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $zipPath,
        public string $zipFilename,
        public int $filesize,
        public string $timestamp
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nafisa Hostel Cloud Backup - {$this->zipFilename}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.backup',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->zipPath)
                ->as($this->zipFilename)
                ->withMime('application/zip'),
        ];
    }
}
