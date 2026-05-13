<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevokedToken extends Model
{
    use HasFactory;

    protected $table = 'revoked_tokens';

    protected $fillable = [
        'tokens',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
