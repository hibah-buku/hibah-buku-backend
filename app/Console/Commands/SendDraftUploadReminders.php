<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Manuscript;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendDraftUploadReminders extends Command
{
    protected $signature = 'reminders:draft-upload';
    protected $description = 'Send draft upload reminders to authors 3 days before deadline';

    public function __construct(protected NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle()
    {
        // Query kontrak dengan deadline 3 hari dari sekarang (+/- 1 hari)
        $targetDate = now()->addDays(3)->startOfDay();
        $nextDay = $targetDate->copy()->addDay();

        $contractsWithDeadline = Contract::whereDate('draft_deadline', '>=', $targetDate)
            ->whereDate('draft_deadline', '<', $nextDay)
            ->where('status', 'contract_validated')
            ->with('author.user')
            ->get();

        $reminderCount = 0;

        foreach ($contractsWithDeadline as $contract) {
            $hasManuscript = Manuscript::where('author_id', $contract->author_id)->exists();

            if (!$hasManuscript && $contract->author && $contract->author->user) {
                $authorEmail = $contract->author->user->email;
                $authorName = $contract->author->user->name;
                $daysRemaining = max(0, now()->diffInDays($contract->draft_deadline, false));
                $uploadUrl = url('/api/manuscripts/upload-draft');

                try {
                    $this->notificationService->sendDraftUploadReminder(
                        $authorEmail,
                        $authorName,
                        (int) $daysRemaining,
                        $contract->draft_deadline->format('d F Y'),
                        $uploadUrl
                    );

                    $reminderCount++;
                    $this->info("✓ Reminder sent to {$authorName} ({$authorEmail})");
                } catch (\Exception $e) {
                    $this->error("✗ Failed to send reminder to {$authorName}: {$e->getMessage()}");
                }
            }
        }

        $this->info("\n📧 Total reminders sent: {$reminderCount}");
    }
}
