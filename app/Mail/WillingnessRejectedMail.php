<?php

namespace App\Mail;

/**
 * Dikirim ke penulis ketika willingness form ditolak.
 * Vars: name, rejection_reason
 */
class WillingnessRejectedMail extends BaseNotificationMail
{
    protected string $templateCode = 'willingness_rejected';

    protected function defaultSubject(): string
    {
        return 'Pengajuan Kesediaan Anda Ditolak';
    }

    protected function defaultView(): string
    {
        return 'emails.willingness.rejected';
    }
}
