<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManuscriptFile extends Model
{
    use HasFactory;

    protected $table = 'manuscript_files';

    public $timestamps = false;

    protected $fillable = [
        'manuscript_id',
        'file_type',
        'file_path',
        'original_name',
        'file_size_kb',
        'mime_type',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    // Relasi: ManuscriptFile milik satu Manuscript
    public function manuscript()
    {
        return $this->belongsTo(Manuscript::class);
    }
}
