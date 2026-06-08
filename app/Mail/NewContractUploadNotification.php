<?php

namespace App\Mail;

/**
 * Dikirim ke semua admin ketika ada kontrak baru diupload oleh penulis.
 * Vars: contract_id, author_name, book_title, file_name, uploaded_at, review_url
 */
class NewContractUploadNotification extends BaseNotificationMail
{
    protected string $templateCode = 'new_contract_upload';

    protected function defaultSubject(): string
    {
        return 'Kontrak Baru Diupload - Perlu Validasi';
    }

    protected function defaultView(): string
    {
        return 'emails.contract.new-upload';
    }
}
