<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewScore extends Model
{
    use HasFactory;

    protected $table = 'review_scores';

    protected $fillable = [
        'assignment_id',
        'rubric_id',
        'score',
        'comment',
    ];

    public function assignment()
    {
        return $this->belongsTo(ReviewerAssignment::class, 'assignment_id');
    }

    public function rubric()
    {
        return $this->belongsTo(ReviewRubric::class, 'rubric_id');
    }
}
