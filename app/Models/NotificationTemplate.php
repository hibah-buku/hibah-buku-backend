<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_name',
        'subject',
        'body_template',
    ];

    public function logs()
    {
        return $this->hasMany(NotificationLog::class, 'template_id');
    }
}