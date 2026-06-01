<?php

namespace App\Mail;

use App\Models\ReviewerAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewerReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $assignment;

    /**
     * Create a new message instance.
     */
    public function __construct(ReviewerAssignment $assignment)
    {
        $this->assignment = $assignment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder (H-3): Batas Waktu Review Naskah ' . $this->assignment->book_title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reviewer_reminder',
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
