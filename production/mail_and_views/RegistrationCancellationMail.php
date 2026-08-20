<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationCancellationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cancellation of Registration - WBSCTVESD',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'registration-cancellation-mail',
            with: [
                'studentName' => $this->data['studentName'] ?? 'Student',
                'registrationNumber' => $this->data['registrationNumber'] ?? 'N/A',
                'rollNumber' => $this->data['rollNumber'] ?? 'N/A',
                'instituteName' => $this->data['instituteName'] ?? 'N/A',
                'courseName' => $this->data['courseName'] ?? 'Diploma in Pharmacy',
                'session' => $this->data['session'] ?? 'N/A',
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
