<?php

namespace App\Mail;

use App\Models\ReviewerAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewerAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(ReviewerAssignment|array $assignmentOrData)
    {
        if ($assignmentOrData instanceof ReviewerAssignment) {
            $assignment = $assignmentOrData;

            $this->viewData = [
                'reviewer_name' => $assignment->reviewer_name
                    ?: $assignment->reviewer?->user?->name
                    ?: $assignment->reviewer_email
                    ?: 'Reviewer',
                'author_name' => $assignment->author?->user?->name
                    ?: $assignment->author_email
                    ?: 'Penulis',
                'book_title' => $assignment->book_title ?: 'Naskah Tanpa Judul',
                'deadline_date' => $assignment->deadline_review
                    ? $assignment->deadline_review->translatedFormat('d F Y')
                    : '-',
                'review_url' => $assignment->manuscript_file_url
                    ?: url('/api/assignments/' . $assignment->id),
            ];
        } else {
            $this->viewData = $assignmentOrData;
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tugas Review Naskah: ' . ($this->viewData['book_title'] ?? 'Naskah baru'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.review.assigned',
            with: $this->viewData,
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
