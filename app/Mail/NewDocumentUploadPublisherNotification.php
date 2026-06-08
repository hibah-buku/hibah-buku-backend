<?php

namespace App\Mail;

/**
 * Dikirim ke semua penerbit ketika ada dokumen baru diupload oleh penulis.
 * Vars: author_name, book_title, document_type_label, uploaded_at, review_url
 */
class NewDocumentUploadPublisherNotification extends BaseNotificationMail
{
    protected string $templateCode = 'new_document_upload_publisher';

    protected function defaultSubject(): string
    {
        return 'Dokumen Kelengkapan Administrasi Baru Diunggah';
    }

    protected function defaultView(): string
    {
        return 'emails.publisher.new-document';
    }
}
