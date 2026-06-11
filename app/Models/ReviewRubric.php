<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewRubric extends Model
{
    use HasFactory;

    protected $table = 'review_rubrics';

    protected $fillable = [
        'criteria_name',
        'max_score',
        'applicable_book_type',
    ];

    public function scores()
    {
        return $this->hasMany(ReviewScore::class, 'rubric_id');
    }
}
