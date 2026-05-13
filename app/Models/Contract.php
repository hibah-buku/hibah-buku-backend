<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contracts';

    protected $fillable = [
        'author_id',
        'file_path',
        'status', // contract_uploaded, contract_validated, contract_rejected
        'draft_deadline',
        'validated_by',
        'validated_at',
        'notes',
    ];

    protected $casts = [
        'draft_deadline' => 'datetime',
        'validated_at' => 'datetime'
    ];

    // Relasi: Contract milik satu Author
    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    // Relasi: Contract divalidasi oleh satu User (Admin)
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
