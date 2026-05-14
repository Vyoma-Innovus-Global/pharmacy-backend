<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $recipientName;

    public function __construct(string $otp, string $recipientName = 'User')
    {
        $this->otp           = $otp;
        $this->recipientName = $recipientName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your OTP - WBSCT&VE&SD Pharmacy Portal',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'otp-mail',
            with: [
                'otp'           => $this->otp,
                'recipientName' => $this->recipientName,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
