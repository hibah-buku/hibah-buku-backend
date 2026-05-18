<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Manuscript;
use App\Models\Deadline;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\PublisherDecision;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class PublisherDashboardSeeder extends Seeder
{
    public function run(): void
    {
        $penerbitRole = Role::firstOrCreate(['name' => 'penerbit']);
        $penulisRole = Role::firstOrCreate(['name' => 'penulis']);

        $publisher = User::firstOrCreate(
            ['email' => 'publisher@hibahbuku.local'],
            [
                'name' => 'Demo Publisher',
                'password' => Hash::make('password123'),
                'role_id' => $penerbitRole->id,
                'status' => 'active',
            ]
        );

        $authorA = User::firstOrCreate(
            ['email' => 'author1@hibahbuku.local'],
            [
                'name' => 'Author Satu',
                'password' => Hash::make('password123'),
                'role_id' => $penulisRole->id,
                'status' => 'active',
            ]
        );

        $authorB = User::firstOrCreate(
            ['email' => 'author2@hibahbuku.local'],
            [
                'name' => 'Author Dua',
                'password' => Hash::make('password123'),
                'role_id' => $penulisRole->id,
                'status' => 'active',
            ]
        );

        $draft = Manuscript::firstOrCreate(
            ['title' => 'Panduan Penerbitan Buku', 'author_id' => $authorA->id],
            ['status' => 'preprint']
        );

        $revision = Manuscript::firstOrCreate(
            ['title' => 'Strategi Penulisan Proposal', 'author_id' => $authorB->id],
            ['status' => 'publisher_revised']
        );

        $approved = Manuscript::firstOrCreate(
            ['title' => 'Teknik Penelitian Akademik', 'author_id' => $authorA->id],
            ['status' => 'ready_to_print']
        );

        $secondDraft = Manuscript::firstOrCreate(
            ['title' => 'Kisah Inspiratif Penerbit', 'author_id' => $authorB->id],
            ['status' => 'preprint']
        );

        $templateApproved = NotificationTemplate::firstOrCreate(
            ['event_name' => 'PublisherApproved'],
            [
                'subject' => 'Naskah disetujui oleh penerbit',
                'body_template' => 'Selamat! Naskah Anda telah disetujui dan siap dicetak.',
            ]
        );

        $templateRevised = NotificationTemplate::firstOrCreate(
            ['event_name' => 'PublisherRevised'],
            [
                'subject' => 'Revisi naskah diperlukan',
                'body_template' => 'Terdapat beberapa catatan revisi dari penerbit. Mohon perbaiki naskah Anda.',
            ]
        );

        NotificationLog::firstOrCreate(
            [
                'template_id' => $templateApproved->id,
                'recipient_id' => $authorA->id,
                'event_name' => 'PublisherApproved',
                'sent_at' => Carbon::now()->subHours(2),
            ],
            [
                'recipient_email' => $authorA->email,
                'payload' => json_encode([
                    'manuscript_title' => $draft->title,
                    'message' => 'Naskah Anda disetujui oleh penerbit.',
                ]),
                'status' => 'sent',
            ]
        );

        NotificationLog::firstOrCreate(
            [
                'template_id' => $templateRevised->id,
                'recipient_id' => $authorB->id,
                'event_name' => 'PublisherRevised',
                'sent_at' => Carbon::now()->subDay(),
            ],
            [
                'recipient_email' => $authorB->email,
                'payload' => json_encode([
                    'manuscript_title' => $revision->title,
                    'message' => 'Revisi naskah diperlukan untuk tata letak dan dokumen pendukung.',
                ]),
                'status' => 'sent',
            ]
        );

        PublisherDecision::firstOrCreate(
            [
                'manuscript_id' => $approved->id,
                'publisher_id' => $publisher->id,
                'decision' => 'approved',
            ],
            [
                'revision_notes' => null,
                'decided_at' => Carbon::now()->subDays(3),
            ]
        );

        PublisherDecision::firstOrCreate(
            [
                'manuscript_id' => $revision->id,
                'publisher_id' => $publisher->id,
                'decision' => 'revised',
            ],
            [
                'revision_notes' => 'Perbaiki bab 2 dan lampiran dokumen administrasi.',
                'decided_at' => Carbon::now()->subDay(),
            ]
        );

        Deadline::firstOrCreate(
            [
                'user_id' => $publisher->id,
                'manuscript_id' => $revision->id,
                'type' => 'revision_upload',
            ],
            [
                'deadline_date' => Carbon::now()->addDays(5)->toDateString(),
                'is_completed' => false,
            ]
        );
    }
}
