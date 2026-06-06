<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manuscript extends Model
{
    use HasFactory;

    protected $table = 'manuscripts';

    protected $fillable = [
        'user_id',
        'contract_id',
        'title',
        'book_type',
        'status',
        'deadline_draft',
        'deadline_revision',
    ];

    protected $casts = [
        'deadline_draft' => 'date',
        'deadline_revision' => 'date',
    ];

    /**
     * Status constants untuk alur naskah
     */
    const STATUS_INITIAL_DRAFT_REQUESTED = 'initial_draft_requested';
    const STATUS_DRAFT_UPLOADED = 'draft_uploaded';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_REVISION_NEEDED = 'revision_needed';
    const STATUS_REVISION_UPLOADED = 'revision_uploaded';
    const STATUS_APPROVED = 'approved';
    const STATUS_PREPRINT = 'preprint';
    const STATUS_PUBLISHER_REVISED = 'publisher_revised';
    const STATUS_TO_PRINT = 'to_print';
    const STATUS_PUBLISHED = 'published';

    /**
     * Daftar semua status yang valid
     */
    const VALID_STATUSES = [
        self::STATUS_INITIAL_DRAFT_REQUESTED,
        self::STATUS_DRAFT_UPLOADED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_REVISION_NEEDED,
        self::STATUS_REVISION_UPLOADED,
        self::STATUS_APPROVED,
        self::STATUS_PREPRINT,
        self::STATUS_PUBLISHER_REVISED,
        self::STATUS_TO_PRINT,
        self::STATUS_PUBLISHED,
    ];

    /**
     * Map status ke label yang human-readable (Bahasa Indonesia)
     */
    const STATUS_LABELS = [
        self::STATUS_INITIAL_DRAFT_REQUESTED => 'Menunggu Upload Naskah Awal',
        self::STATUS_DRAFT_UPLOADED => 'Naskah Awal Telah Diunggah',
        self::STATUS_UNDER_REVIEW => 'Sedang Direview',
        self::STATUS_REVISION_NEEDED => 'Perlu Revisi',
        self::STATUS_REVISION_UPLOADED => 'Revisi Telah Diunggah',
        self::STATUS_APPROVED => 'Naskah Disetujui',
        self::STATUS_PREPRINT => 'Pra-Cetak (Penerbit)',
        self::STATUS_PUBLISHER_REVISED => 'Revisi dari Penerbit',
        self::STATUS_TO_PRINT => 'Siap Cetak',
        self::STATUS_PUBLISHED => 'Telah Diterbitkan',
    ];

    // Relasi: Manuscript milik satu User (Penulis)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: Manuscript terkait satu Contract
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    // Relasi: Manuscript punya satu BookMetadata
    public function bookMetadata()
    {
        return $this->hasOne(BookMetadata::class);
    }

    // Relasi: Manuscript punya banyak ManuscriptFile (draft_awal, revisi_1, dll)
    public function manuscriptFiles()
    {
        return $this->hasMany(ManuscriptFile::class);
    }

    // Alias untuk user (dipakai PublisherController)
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi: Manuscript punya banyak PublisherCheck
    public function publisherChecks()
    {
        return $this->hasMany(PublisherCheck::class);
    }

    /**
     * Get file draft terbaru (latest uploaded file)
     */
    public function latestFile()
    {
        return $this->hasOne(ManuscriptFile::class)->latestOfMany('uploaded_at');
    }

    /**
     * Get label status dalam Bahasa Indonesia
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Cek apakah penulis bisa upload/re-upload draft
     */
    public function canUploadDraft(): bool
    {
        return in_array($this->status, [
            self::STATUS_INITIAL_DRAFT_REQUESTED,
            self::STATUS_REVISION_NEEDED,
        ]);
    }
}
