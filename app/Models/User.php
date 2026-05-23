<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'status' // active, inactive
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role->name,
        ];
    }

    // Relasi: User punya satu Role
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Relasi: User bisa jadi Author (One-to-One)
    public function author()
    {
        return $this->hasOne(Author::class);
    }

    // Relasi: User (Admin) bisa memvalidasi banyak Contracts
    public function validatedContracts()
    {
        return $this->hasMany(Contract::class, 'validated_by');
    }

    // Relasi: User (Penulis) bisa punya banyak Manuscripts
    public function manuscripts()
    {
        return $this->hasMany(Manuscript::class);
    }

    // Relasi: User (Reviewer) bisa ditugaskan di banyak assignments (Kelompok 3)
    // public function reviewerAssignments()
    // {
    //     return $this->hasMany(ReviewerAssignment::class, 'reviewer_id');
    // }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
