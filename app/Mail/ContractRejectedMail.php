<?php

namespace App\Mail;

/**
 * Dikirim ke penulis ketika kontrak mereka ditolak oleh admin.
 * Vars: name, book_title, rejection_reason, resubmit_url
 */
class ContractRejectedMail extends BaseNotificationMail
{
    protected string $templateCode = 'contract_rejected';

    protected function defaultSubject(): string
    {
        return 'Kontrak Hibah Buku Anda Ditolak';
    }

    protected function defaultView(): string
    {
        return 'emails.contract.rejected';
    }
}
