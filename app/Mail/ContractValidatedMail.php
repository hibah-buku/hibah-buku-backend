<?php

namespace App\Mail;

/**
 * Dikirim ke penulis setelah admin memvalidasi kontrak.
 * Vars: name, deadline_upload, upload_url
 */
class ContractValidatedMail extends BaseNotificationMail
{
    protected string $templateCode = 'contract_validated';

    protected function defaultSubject(): string
    {
        return 'Kontrak Anda Telah Divalidasi – Silakan Unggah Draft Awal';
    }

    protected function defaultView(): string
    {
        return 'emails.contract.validated';
    }
}
