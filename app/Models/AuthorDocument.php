<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorDocument extends Model
{
    use HasFactory;

    protected $table = 'author_documents';

    public $timestamps = false;

    protected $fillable = [
        'manuscript_id',
        'document_type',
        'file_path',
        'file_size_kb',
        'is_verified',
        'uploaded_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    // Relasi: AuthorDocument milik satu Manuscript
    public function manuscript()
    {
        return $this->belongsTo(Manuscript::class);
    }
}
