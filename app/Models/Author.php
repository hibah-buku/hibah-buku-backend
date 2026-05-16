<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasFactory;

    protected $table = 'authors';

    protected $fillable = [
        'user_id',
        'institution',
        'field_of_study',
    ];

    // Relasi: Author milik satu User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: Author punya satu Contract (One-to-One)
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    // Relasi: Author punya satu Willingness Form (via email matching atau logic lain)
    public function willingnessForm()
    {
        return $this->hasOne(WillingnessForm::class, 'main_author_email', 'user.email');
    }
}
