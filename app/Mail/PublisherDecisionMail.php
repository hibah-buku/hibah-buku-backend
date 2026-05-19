<?php

namespace App\Mail;

/**
 * Dikirim ke penulis setelah penerbit memberi keputusan.
 * Vars: name, book_title, decision (approved|revised), notes, action_url
 */
class PublisherDecisionMail extends BaseNotificationMail
{
    protected string $templateCode = 'publisher_decision';

    protected function defaultSubject(): string
    {
        $decision = $this->viewData['decision'] ?? 'approved';
        return $decision === 'approved'
            ? 'Selamat! Naskah Anda Telah Disetujui untuk Dicetak'
            : 'Naskah Anda Memerlukan Revisi Administrasi';
    }

    protected function defaultView(): string
    {
        $decision = $this->viewData['decision'] ?? 'approved';
        return $decision === 'approved'
            ? 'emails.preprint.approved'
            : 'emails.preprint.revised';
    }
}
