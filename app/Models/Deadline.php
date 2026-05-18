<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deadline extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'manuscript_id',
        'type',
        'deadline_date',
        'is_completed',
    ];

    protected $casts = [
        'deadline_date' => 'date',
        'is_completed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function manuscript()
    {
        return $this->belongsTo(Manuscript::class);
    }

    public function reminderLogs()
    {
        return $this->hasMany(ReminderLog::class);
    }
}