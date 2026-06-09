<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewerAssignment extends Model
{
    use HasFactory;

    protected $table = 'reviewer_assignments';

    protected $fillable = [
        'manuscript_id',
        'reviewer_id',
        'reviewer_name',
        'reviewer_email',
        'book_title',
        'author_id',
        'author_email',
        'manuscript_file_url',
        'status',
        'deadline_review',
        'rekomendasi_akhir',
        'general_comments',
        'review_notes',
        'final_score',
        'submitted_at',
    ];

    protected $casts = [
        'deadline_review' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function manuscript()
    {
        return $this->belongsTo(Manuscript::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function scores()
    {
        return $this->hasMany(ReviewScore::class, 'assignment_id');
    }
}
