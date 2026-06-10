<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reviewer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email'
    ];

    /**
     * Get the user that owns the reviewer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the assignments for the reviewer.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ReviewerAssignment::class, 'reviewer_id');
    }
}
