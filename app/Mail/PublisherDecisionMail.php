<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;

/**
 * Dikirim ke penulis setelah penerbit memberi keputusan.
 * Vars: name, book_title, decision (approved|revised), notes, action_url
 */
class PublisherDecisionMail extends BaseNotificationMail
{
    protected string $templateCode = 'publisher_decision';

    public function content(): Content
    {
        return new Content(
            view: $this->defaultView(),
            with: $this->viewData
        );
    }

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
