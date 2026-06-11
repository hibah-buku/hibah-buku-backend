<?php

namespace App\Mail;

/**
 * Dikirim ke semua admin ketika ada willingness form baru masuk.
 * Vars: form_id, author_name, book_title, submitted_at, review_url
 */
class NewWillingnessFormAdminNotification extends BaseNotificationMail
{
    protected string $templateCode = 'new_willingness_form';

    protected function defaultSubject(): string
    {
        return 'Formulir Kesediaan Baru Masuk - Perlu Validasi';
    }

    protected function defaultView(): string
    {
        return 'emails.willingness.new-submission';
    }
}
