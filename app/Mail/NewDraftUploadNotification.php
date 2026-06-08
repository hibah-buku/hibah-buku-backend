<?php

namespace App\Mail;

/**
 * Dikirim ke semua admin ketika ada draft naskah baru diupload oleh penulis.
 * Vars: manuscript_id, author_name, book_title, book_type, uploaded_at, review_url
 */
class NewDraftUploadNotification extends BaseNotificationMail
{
    protected string $templateCode = 'new_draft_upload';

    protected function defaultSubject(): string
    {
        return 'Draft Naskah Baru Diupload - Perlu Plotting Reviewer';
    }

    protected function defaultView(): string
    {
        return 'emails.manuscript.new-upload';
    }
}
