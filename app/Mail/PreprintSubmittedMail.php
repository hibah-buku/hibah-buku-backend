<?php

namespace App\Mail;

/**
 * Dikirim ke penerbit saat penulis mengunggah naskah ke tahap pra-cetak.
 * Vars: author_name, book_title, preprint_url
 */
class PreprintSubmittedMail extends BaseNotificationMail
{
    protected string $templateCode = 'preprint_submitted';

    protected function defaultSubject(): string
    {
        return 'Naskah Baru Masuk Tahap Pra-Cetak';
    }

    protected function defaultView(): string
    {
        return 'emails.preprint.submitted';
    }
}
