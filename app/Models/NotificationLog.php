<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'notification_template_id',
        'template_code',
        'recipient_email',
        'recipient_name',
        'notifiable_type',
        'notifiable_id',
        'subject',
        'status',
        'error_message',
        'payload',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsSent(): void
    {
        $this->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update(['status' => 'failed', 'error_message' => $reason]);
    }

    // Scopes
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByTemplate($query, string $code)
    {
        return $query->where('template_code', $code);
    }
}
