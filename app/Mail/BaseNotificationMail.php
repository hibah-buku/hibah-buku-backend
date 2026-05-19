<?php

namespace App\Mail;

use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

abstract class BaseNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    protected string $templateCode;
    protected array $viewData;
    protected ?object $notifiable;

    public function __construct(array $viewData = [], ?object $notifiable = null)
    {
        $this->viewData  = $viewData;
        $this->notifiable = $notifiable;
    }

    public function envelope(): Envelope
    {
        $template = NotificationTemplate::findByCode($this->templateCode);
        $subject  = $template
            ? $this->interpolate($template->subject, $this->viewData)
            : $this->defaultSubject();

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $template = NotificationTemplate::findByCode($this->templateCode);
        $view     = $template ? $template->view : $this->defaultView();

        return new Content(view: $view, with: $this->viewData);
    }

    /** Log the email after sending */
    public function logSent(string $email, string $name = ''): NotificationLog
    {
        $template = NotificationTemplate::findByCode($this->templateCode);

        $log = NotificationLog::create([
            'notification_template_id' => $template?->id,
            'template_code'            => $this->templateCode,
            'recipient_email'          => $email,
            'recipient_name'           => $name,
            'notifiable_type'          => $this->notifiable ? get_class($this->notifiable) : null,
            'notifiable_id'            => $this->notifiable?->id,
            'subject'                  => $this->envelope()->subject,
            'status'                   => 'sent',
            'payload'                  => $this->viewData,
            'sent_at'                  => now(),
        ]);

        return $log;
    }

    /** Log a failed send */
    public static function logFailed(
        string $templateCode,
        string $email,
        string $errorMessage,
        array $payload = []
    ): NotificationLog {
        $template = NotificationTemplate::findByCode($templateCode);

        return NotificationLog::create([
            'notification_template_id' => $template?->id,
            'template_code'            => $templateCode,
            'recipient_email'          => $email,
            'subject'                  => $template?->subject ?? '-',
            'status'                   => 'failed',
            'error_message'            => $errorMessage,
            'payload'                  => $payload,
        ]);
    }

    protected function interpolate(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $text = str_replace(":$key", $value, $text);
            }
        }
        return $text;
    }

    abstract protected function defaultSubject(): string;
    abstract protected function defaultView(): string;
}
