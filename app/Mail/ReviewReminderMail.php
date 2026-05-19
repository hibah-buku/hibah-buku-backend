<?php

namespace App\Mail;

/**
 * Reminder harian ke reviewer jika belum submit review (H-3 s/d H).
 * Vars: reviewer_name, book_title, days_remaining, deadline_date, review_url
 */
class ReviewReminderMail extends BaseNotificationMail
{
    protected string $templateCode = 'review_reminder';

    protected function defaultSubject(): string
    {
        return 'Reminder: Segera Selesaikan Review Naskah';
    }

    protected function defaultView(): string
    {
        return 'emails.reminder.review';
    }
}
