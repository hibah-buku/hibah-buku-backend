<?php

namespace App\Console\Commands;

use App\Models\ReviewerAssignment;
use App\Mail\ReviewerReminderMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendReviewerReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-reviewer-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily reminder emails to reviewers 3 days before their deadline.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to send reviewer reminders...');

        // H-3: deadline adalah tepat 3 hari dari hari ini
        $targetDate = Carbon::now()->addDays(3)->toDateString();

        $assignments = ReviewerAssignment::whereIn('status', ['assigned', 'under_review'])
            ->whereNotNull('deadline_review')
            ->whereDate('deadline_review', $targetDate)
            ->get();

        $count = 0;
        foreach ($assignments as $assignment) {
            try {
                Mail::to($assignment->reviewer_email)->send(new ReviewerReminderMail($assignment));
                $this->line("Sent reminder to: {$assignment->reviewer_email} for manuscript #{$assignment->manuscript_id}");
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to send reminder to {$assignment->reviewer_email}: " . $e->getMessage());
            }
        }

        $this->info("Completed sending reminders. Total sent: {$count}");
    }
}
