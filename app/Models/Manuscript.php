<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manuscript extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id',
        'title',
        'status',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function publisherChecks()
    {
        return $this->hasMany(PublisherCheck::class);
    }

    public function publisherDecisions()
    {
        return $this->hasMany(PublisherDecision::class);
    }

    public function deadlines()
    {
        return $this->hasMany(Deadline::class);
    }
}
