<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookMetadata extends Model
{
    use HasFactory;

    protected $table = 'book_metadata';

    public $timestamps = false;

    protected $fillable = [
        'manuscript_id',
        'abstract',
        'page_count',
        'category',
        'field_of_study',
        'institution',
    ];

    // Relasi: BookMetadata milik satu Manuscript
    public function manuscript()
    {
        return $this->belongsTo(Manuscript::class);
    }
}
