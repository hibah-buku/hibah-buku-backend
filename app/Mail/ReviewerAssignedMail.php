<?php

namespace App\Mail;

/**
 * Dikirim ke reviewer setelah di-plot oleh admin.
 * Vars: reviewer_name, author_name, book_title, deadline_date, review_url
 */
class ReviewerAssignedMail extends BaseNotificationMail
{
    protected string $templateCode = 'reviewer_assigned';

    protected function defaultSubject(): string
    {
        return 'Penugasan Review Naskah Buku';
    }

    protected function defaultView(): string
    {
        return 'emails.review.assigned';
    }
}
