<?php

namespace App\Mail;

/**
 * Dikirim ke semua penerbit ketika penulis mengupload revisi naskah.
 * Vars: manuscript_id, author_name, book_title, uploaded_at, review_url
 */
class RevisionUploadedPublisherNotification extends BaseNotificationMail
{
    protected string $templateCode = 'revision_uploaded_publisher';

    protected function defaultSubject(): string
    {
        return 'Revisi Naskah Telah Diupload – Siap untuk Diperiksa';
    }

    protected function defaultView(): string
    {
        return 'emails.draft.revision-uploaded';
    }
}
