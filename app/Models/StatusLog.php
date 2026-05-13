<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusLog extends Model
{
     use HasFactory;

    protected $table = 'status_logs';

    protected $fillable = [
        'author_id',
        'contract_id',
        'from_status',
        'to_status',
        'triggered_by', // 'system' atau 'admin:id'
        'triggered_at',
        'notes',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
    ];

    // Relasi: Log milik satu Author
    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    // Relasi: Log terkait satu Contract (Opsional)
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
