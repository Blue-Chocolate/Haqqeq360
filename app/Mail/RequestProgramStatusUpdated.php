<?php

namespace App\Mail;

use App\Models\RequestProgram;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestProgramStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public RequestProgram $requestProgram;

    /**
     * Create a new message instance.
     */
    public function __construct(RequestProgram $requestProgram)
    {
        $this->requestProgram = $requestProgram;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $status = ucfirst($this->requestProgram->status);
        
        return new Envelope(
            subject: "Program Request {$status}: {$this->requestProgram->program_name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.request-program-status',
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