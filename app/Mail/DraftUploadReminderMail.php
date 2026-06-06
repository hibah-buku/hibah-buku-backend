<?php

namespace App\Mail;

/**
 * Reminder harian ke penulis jika belum upload draft awal (H-3 s/d H).
 * Vars: name, days_remaining, deadline_date, upload_url
 */
class DraftUploadReminderMail extends BaseNotificationMail
{
    protected string $templateCode = 'draft_upload_reminder';

    protected function defaultSubject(): string
    {
        return 'Reminder: Segera Unggah Draft Awal Buku Anda';
    }

    protected function defaultView(): string
    {
        return 'emails.reminder.draft_upload';
    }
}
