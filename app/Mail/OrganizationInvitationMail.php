<?php

namespace App\Mail;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrganizationInvitation $invitation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation à rejoindre ' . $this->invitation->organization->name . ' sur Certy',
            replyTo: [$this->invitation->inviter?->email ?? config('mail.from.address')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organization-invitation',
        );
    }
}
