<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReminderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'deadline_id',
        'user_id',
        'days_before',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function deadline()
    {
        return $this->belongsTo(Deadline::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}