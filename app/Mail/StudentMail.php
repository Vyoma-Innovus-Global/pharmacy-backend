<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


class StudentMail extends Mailable
{
    use Queueable, SerializesModels;

    private $name; 
    private $form_num; 
    private $email; 
    private $reg_no; 

    /**
     * Create a new message instance.
     */
    public function __construct($name,$form_num,$email,$reg_no)
    {
        $this->name = $name;
        $this->form_num = $form_num;
        $this->email = $email;
        $this->reg_no = $reg_no; 
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Registration Generated',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'registration-confirmation-mail',
            with: [
                'fullname' => $this->name, 
                'form_num' => $this->form_num,
                'email' => $this->email,
                'reg_no' => $this->reg_no,
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

