<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Deadline;
use App\Models\ReminderLog;
use App\Services\NotificationService;
use Carbon\Carbon;

#[Signature('app:send-deadline-reminders')]
#[Description('Kirim reminder email H-3, H-2, H-1 kepada penulis dan reviewer yang belum menyelesaikan tugas')]
class SendDeadlineReminders extends Command
{
    public function __construct(protected NotificationService $notificationService)
    {
        parent::__construct();
    }

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
                $alreadySent = ReminderLog::where('deadline_id', $deadline->id)
                    ->where('user_id', $deadline->user_id)
                    ->where('days_before', $daysBefore)
                    ->whereDate('sent_at', $today)
                    ->exists();

                if ($alreadySent) continue;

                $user = $deadline->user;
                $type = $deadline->type;
                $deadlineDate = $deadline->deadline_date->format('d-m-Y');
                $uploadUrl = url('/author/upload-draft');

                if ($type === 'draft_upload') {
                    $this->notificationService->sendDraftUploadReminder(
                        $user->email, $user->name, $daysBefore, $deadlineDate, $uploadUrl
                    );
                } elseif ($type === 'revision_upload') {
                    $this->notificationService->sendDraftUploadReminder(
                        $user->email, $user->name, $daysBefore, $deadlineDate,
                        url('/author/upload-revision')
                    );
                } elseif ($type === 'review_submission') {
                    $bookTitle = $deadline->manuscript?->title ?? 'Naskah';
                    $this->notificationService->sendReviewReminder(
                        $user->email, $user->name, $bookTitle, $daysBefore, $deadlineDate,
                        url('/reviewer/manuscripts')
                    );
                }

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