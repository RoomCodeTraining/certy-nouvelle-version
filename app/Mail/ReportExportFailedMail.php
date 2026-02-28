<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportExportFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $reason,
        public ?string $period = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Export automatique Reporting — échec',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.report-export-failed',
        );
    }
}
