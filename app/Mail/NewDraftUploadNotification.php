<?php

namespace App\Mail;

/**
 * Dikirim ke semua admin ketika penulis mengupload draft naskah awal.
 * Vars: manuscript_id, author_name, book_title, book_type, uploaded_at, review_url, admin_name
 */
class NewDraftUploadNotification extends BaseNotificationMail
{
    protected string $templateCode = 'new_draft_upload';

    protected function defaultSubject(): string
    {
        return 'Draft Naskah Baru Diupload – Siap untuk Plotting Reviewer';
    }

    protected function defaultView(): string
    {
        return 'emails.draft.new-upload';
    }
}
