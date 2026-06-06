<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Deadline;
use App\Models\ReminderLog;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

#[Signature('app:send-deadline-reminders')]
#[Description('Kirim reminder email H-3, H-2, H-1 kepada penulis dan reviewer yang belum menyelesaikan tugas')]
class SendDeadlineReminders extends Command
{
    public function handle()
    {
        $today = Carbon::today();
        $reminderDays = [3, 2, 1];

        foreach ($reminderDays as $daysBefore) {
            $targetDate = $today->copy()->addDays($daysBefore);

            $deadlines = Deadline::with(['user', 'manuscript'])
                ->whereDate('deadline_date', $targetDate)
                ->where('is_completed', false)
                ->get();

            foreach ($deadlines as $deadline) {
                // Cek apakah reminder hari ini sudah dikirim
                $alreadySent = ReminderLog::where('deadline_id', $deadline->id)
                    ->where('user_id', $deadline->user_id)
                    ->where('days_before', $daysBefore)
                    ->whereDate('sent_at', $today)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $user = $deadline->user;
                $type = $deadline->type;

                // Tentukan pesan sesuai tipe deadline
                if ($type === 'draft_upload') {
                    $subject = 'Reminder: Upload Draft Awal';
                    $body = "Yth. {$user->name},\n\nAnda memiliki {$daysBefore} hari lagi untuk mengunggah draft awal buku. Harap segera unggah sebelum deadline.\n\nTerima kasih.";
                } elseif ($type === 'revision_upload') {
                    $subject = 'Reminder: Upload Revisi Naskah';
                    $body = "Yth. {$user->name},\n\nAnda memiliki {$daysBefore} hari lagi untuk mengunggah revisi naskah. Harap segera unggah sebelum deadline.\n\nTerima kasih.";
                } elseif ($type === 'review_submission') {
                    $subject = 'Reminder: Submit Review Naskah';
                    $body = "Yth. {$user->name},\n\nAnda memiliki {$daysBefore} hari lagi untuk menyelesaikan review naskah. Harap segera submit sebelum deadline.\n\nTerima kasih.";
                } else {
                    continue;
                }

                // Kirim email
                Mail::raw($body, function ($message) use ($user, $subject) {
                    $message->to($user->email)->subject($subject);
                });

                // Catat di reminder_logs
                ReminderLog::create([
                    'deadline_id' => $deadline->id,
                    'user_id'     => $deadline->user_id,
                    'days_before' => $daysBefore,
                    'sent_at'     => now(),
                ]);

                $this->info("Reminder terkirim ke {$user->email} ({$type}, H-{$daysBefore})");
            }
        }

        $this->info('Semua reminder sudah diproses.');
    }
}