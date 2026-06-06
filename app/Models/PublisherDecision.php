<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublisherDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'manuscript_id',
        'publisher_id',
        'decision',
        'revision_notes',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function manuscript()
    {
        return $this->belongsTo(Manuscript::class);
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }
}