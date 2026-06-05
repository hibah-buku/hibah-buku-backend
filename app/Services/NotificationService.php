<?php

namespace App\Services;

use App\Mail\AccountCreatedMail;
use App\Mail\BaseNotificationMail;
use App\Mail\ContractValidatedMail;
use App\Mail\DraftUploadReminderMail;
use App\Mail\PreprintSubmittedMail;
use App\Mail\PublisherDecisionMail;
use App\Mail\ReviewCompletedMail;
use App\Mail\ReviewerAssignedMail;
use App\Mail\ReviewReminderMail;
use App\Mail\WillingnessRejectedMail;
use App\Models\NotificationLog;
use App\Mail\NewWillingnessFormAdminNotification;
use App\Mail\NewContractUploadNotification;
use App\Mail\ContractRejectedMail;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Generic send method — logs success and failure automatically.
     */
    public function send(
        BaseNotificationMail $mailable,
        string $email,
        string $name = '',
        ?object $notifiable = null
    ): NotificationLog {
        try {
            Mail::to($email, $name)->send($mailable);
            return $mailable->logSent($email, $name);
        } catch (\Throwable $e) {
            Log::error('Email send failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return BaseNotificationMail::logFailed(
                templateCode: $this->resolveTemplateCode($mailable),
                email: $email,
                errorMessage: $e->getMessage(),
                payload: []
            );
        }
    }

    // ──────────────────────────────────────────────
    // Convenience methods per notification type
    // ──────────────────────────────────────────────

    public function sendNewWillingnessFormToAdmins(
        int $formId,
        string $authorName,
        string $bookTitle,
        string $createdAt,
        string $reviewUrl
    ): array {

        $adminRole = Role::where('name', 'admin')->first();
        $admins = User::where('role_id', $adminRole->id)->get();

        if (!$createdAt) {
            $createdAt = now()->format('Y-m-d H:i:s');
        }

        $results = [];

        foreach ($admins as $admin) {
            $results[] = $this->send(
                new NewWillingnessFormAdminNotification([
                    'form_id'       => $formId,
                    'author_name'   => $authorName,
                    'book_title'    => $bookTitle,
                    'submitted_at'  => $createdAt,
                    'review_url'    => $reviewUrl,
                ]),
                $admin->email,
                $admin->name
            );
        }

        return $results;
    }

    public function sendAccountCreated(
        string $email, string $name, string $password, string $loginUrl
    ): NotificationLog {
        return $this->send(
            new AccountCreatedMail(compact('name', 'email', 'password') + ['login_url' => $loginUrl]),
            $email, $name
        );
    }

    public function sendWillingnessRejected(
        string $email, string $name, ?string $rejectionReason = null
    ): NotificationLog {
        return $this->send(
            new WillingnessRejectedMail([
                'name' => $name,
                'rejection_reason' => $rejectionReason,
            ]),
            $email, $name
        );
    }

    public function sendNewContractUploadToAdmins(
        int $contractId,
        string $authorName,
        string $bookTitle,
        string $fileName,
        string $uploadedAt,
        string $reviewUrl
    ) : array {
        $adminRole = Role::where('name', 'admin')->first();
        $admins = User::where('role_id', $adminRole->id)->get();

        if (!$uploadedAt) {
            $uploadedAt = now()->format('Y-m-d H:i:s');
        }

        $result = [];

        foreach ($admins as $admin) {
            $result[] = $this->send(
                new NewContractUploadNotification([
                    'contract_id'  => $contractId,
                    'author_name'  => $authorName,
                    'book_title'   => $bookTitle,
                    'file_name'    => $fileName,
                    'uploaded_at'  => $uploadedAt,
                    'review_url'   => $reviewUrl,
                ]),
                $admin->email,
                $admin->name
            );
        }

        return $result;
    }

    public function sendContractValidated(
        string $email,
        string $name,
        string $deadlineUpload,
        string $uploadUrl
    ): NotificationLog {
        return $this->send(
            new ContractValidatedMail(['name' => $name, 'deadline_upload' => $deadlineUpload, 'upload_url' => $uploadUrl]),
            $email, $name
        );
    }

    public function sendContractRejected(
        string $email,
        string $authorName,
        string $bookTitle,
        ?string $rejectionReason,
        string $resubmitUrl,
    ): NotificationLog {
        return $this->send(
            new ContractRejectedMail([
                'name'             => $authorName,
                'book_title'       => $bookTitle,
                'rejection_reason' => $rejectionReason,
                'resubmit_url'     => $resubmitUrl,
            ]),
            $email,
            $authorName
        );
    }

    public function sendDraftUploadReminder(
        string $email, string $name, int $daysRemaining, string $deadlineDate, string $uploadUrl
    ): NotificationLog {
        return $this->send(
            new DraftUploadReminderMail([
                'name'           => $name,
                'days_remaining' => $daysRemaining,
                'deadline_date'  => $deadlineDate,
                'upload_url'     => $uploadUrl,
            ]),
            $email, $name
        );
    }

    public function sendReviewerAssigned(
        string $email, string $reviewerName, string $authorName,
        string $bookTitle, string $deadlineDate, string $reviewUrl
    ): NotificationLog {
        return $this->send(
            new ReviewerAssignedMail([
                'reviewer_name' => $reviewerName,
                'author_name'   => $authorName,
                'book_title'    => $bookTitle,
                'deadline_date' => $deadlineDate,
                'review_url'    => $reviewUrl,
            ]),
            $email, $reviewerName
        );
    }

    public function sendReviewReminder(
        string $email, string $reviewerName, string $bookTitle,
        int $daysRemaining, string $deadlineDate, string $reviewUrl
    ): NotificationLog {
        return $this->send(
            new ReviewReminderMail([
                'reviewer_name'  => $reviewerName,
                'book_title'     => $bookTitle,
                'days_remaining' => $daysRemaining,
                'deadline_date'  => $deadlineDate,
                'review_url'     => $reviewUrl,
            ]),
            $email, $reviewerName
        );
    }

    public function sendReviewCompleted(
        string $email, string $name, string $bookTitle,
        string $deadlineRevision, string $reviewUrl
    ): NotificationLog {
        return $this->send(
            new ReviewCompletedMail([
                'name'              => $name,
                'book_title'        => $bookTitle,
                'deadline_revision' => $deadlineRevision,
                'review_url'        => $reviewUrl,
            ]),
            $email, $name
        );
    }

    public function sendPreprintSubmitted(
        string $email, string $authorName, string $bookTitle, string $preprintUrl
    ): NotificationLog {
        return $this->send(
            new PreprintSubmittedMail([
                'author_name'  => $authorName,
                'book_title'   => $bookTitle,
                'preprint_url' => $preprintUrl,
            ]),
            $email
        );
    }

    public function sendPublisherDecision(
        string $email, string $name, string $bookTitle,
        string $decision, string $notes, string $actionUrl
    ): NotificationLog {
        return $this->send(
            new PublisherDecisionMail([
                'name'       => $name,
                'book_title' => $bookTitle,
                'decision'   => $decision,
                'notes'      => $notes,
                'action_url' => $actionUrl,
            ]),
            $email, $name
        );
    }

    // ──────────────────────────────────────────────

    private function resolveTemplateCode(BaseNotificationMail $mailable): string
    {
        return (new \ReflectionProperty($mailable, 'templateCode'))->getValue($mailable);
    }
}
