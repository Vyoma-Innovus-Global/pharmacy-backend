<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $noticeMessage;

    public function __construct(string $noticeMessage)
    {
        $this->noticeMessage = $noticeMessage;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Notice for Submission of Pending Documents – Pharmacy Registration',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'notice-mail',
            with: [
                'noticeMessage' => $this->noticeMessage,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
