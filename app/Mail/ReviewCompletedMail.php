<?php

namespace App\Mail;

/**
 * Dikirim ke penulis setelah semua reviewer selesai memberi review.
 * Vars: name, book_title, review_url, deadline_revision
 */
class ReviewCompletedMail extends BaseNotificationMail
{
    protected string $templateCode = 'review_completed';

    protected function defaultSubject(): string
    {
        return 'Review Naskah Anda Telah Selesai – Silakan Lakukan Revisi';
    }

    protected function defaultView(): string
    {
        return 'emails.review.completed';
    }
}
