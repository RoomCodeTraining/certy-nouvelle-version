<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportingExportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $filePath,
        public ?string $dateFrom,
        public ?string $dateTo,
        public int $rowCount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Export Reporting attestations externes prêt',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reporting-export-ready',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->filePath)
                ->as(basename($this->filePath))
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
