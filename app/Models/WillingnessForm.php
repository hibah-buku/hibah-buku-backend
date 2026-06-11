<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WillingnessForm extends Model
{
    use HasFactory;

    protected $table = 'willingness_forms';

    protected $fillable = [
        'main_author_name',
        'main_author_email',
        'main_author_institution',
        'main_author_phone',

        'co_author_1_name',
        'co_author_1_email',
        'co_author_1_institution',

        'co_author_2_name',
        'co_author_2_email',
        'co_author_2_institution',

        'co_author_3_name',
        'co_author_3_email',
        'co_author_3_institution',

        'co_author_4_name',
        'co_author_4_email',
        'co_author_4_institution',

        'book_title',
        'book_type', // bukuajar, bukureferensi
        'field_of_study',
        'book_abstract',
        'target_audience',

        'rejection_reason',
        'rejected_at',

        'status', // pending, approved, rejected
        'admin_notes',
    ];
    protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'rejected_at' => 'datetime',
];
}
