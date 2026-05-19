<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'code'                => 'account_created',
                'name'                => 'Akun Penulis Dibuat',
                'subject'             => 'Akun Sistem Hibah Buku Anda Telah Dibuat',
                'view'                => 'emails.account.created',
                'available_variables' => ['name', 'email', 'password', 'login_url'],
            ],
            [
                'code'                => 'contract_validated',
                'name'                => 'Kontrak Divalidasi',
                'subject'             => 'Kontrak Anda Telah Divalidasi – Silakan Unggah Draft Awal',
                'view'                => 'emails.contract.validated',
                'available_variables' => ['name', 'deadline_upload', 'upload_url'],
            ],
            [
                'code'                => 'draft_upload_reminder',
                'name'                => 'Reminder Upload Draft',
                'subject'             => 'Reminder: Segera Unggah Draft Awal Buku Anda',
                'view'                => 'emails.reminder.draft_upload',
                'available_variables' => ['name', 'days_remaining', 'deadline_date', 'upload_url'],
            ],
            [
                'code'                => 'reviewer_assigned',
                'name'                => 'Penugasan Reviewer',
                'subject'             => 'Penugasan Review Naskah Buku',
                'view'                => 'emails.review.assigned',
                'available_variables' => ['reviewer_name', 'author_name', 'book_title', 'deadline_date', 'review_url'],
            ],
            [
                'code'                => 'review_reminder',
                'name'                => 'Reminder Review',
                'subject'             => 'Reminder: Segera Selesaikan Review Naskah',
                'view'                => 'emails.reminder.review',
                'available_variables' => ['reviewer_name', 'book_title', 'days_remaining', 'deadline_date', 'review_url'],
            ],
            [
                'code'                => 'review_completed',
                'name'                => 'Review Selesai (notif ke penulis)',
                'subject'             => 'Review Naskah Anda Telah Selesai – Silakan Lakukan Revisi',
                'view'                => 'emails.review.completed',
                'available_variables' => ['name', 'book_title', 'deadline_revision', 'review_url'],
            ],
            [
                'code'                => 'preprint_submitted',
                'name'                => 'Naskah Masuk Pra-Cetak (notif ke penerbit)',
                'subject'             => 'Naskah Baru Masuk Tahap Pra-Cetak',
                'view'                => 'emails.preprint.submitted',
                'available_variables' => ['author_name', 'book_title', 'preprint_url'],
            ],
            [
                'code'                => 'publisher_decision',
                'name'                => 'Keputusan Penerbit',
                'subject'             => 'Keputusan Penerbit atas Naskah Anda',
                'view'                => 'emails.preprint.approved', // overridden by PublisherDecisionMail
                'available_variables' => ['name', 'book_title', 'decision', 'notes', 'action_url'],
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['code' => $template['code']],
                $template
            );
        }
    }
}
