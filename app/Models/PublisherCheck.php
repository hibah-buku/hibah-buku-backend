<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublisherCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'manuscript_id',
        'publisher_id',
        'check_notes',
        'cover_design_ok',
        'page_count_ok',
        'admin_docs_ok',
    ];

    protected $casts = [
        'cover_design_ok' => 'boolean',
        'page_count_ok' => 'boolean',
        'admin_docs_ok' => 'boolean',
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