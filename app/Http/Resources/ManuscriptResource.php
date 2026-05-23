<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\Manuscript;

class ManuscriptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'contract_id' => $this->contract_id,
            'title' => $this->title,
            'book_type' => $this->book_type,
            'status' => [
                'code' => $this->status,
                'label' => $this->status_label,
            ],
            'deadlines' => [
                'draft' => $this->deadline_draft?->toDateString(),
                'revision' => $this->deadline_revision?->toDateString(),
            ],
            'book_metadata' => $this->when($this->relationLoaded('bookMetadata') && $this->bookMetadata, [
                'abstract' => $this->bookMetadata?->abstract,
                'page_count' => $this->bookMetadata?->page_count,
                'category' => $this->bookMetadata?->category,
                'field_of_study' => $this->bookMetadata?->field_of_study,
                'institution' => $this->bookMetadata?->institution,
            ]),
            'latest_file' => $this->when($this->relationLoaded('latestFile') && $this->latestFile, function () {
                return [
                    'id' => $this->latestFile->id,
                    'file_type' => $this->latestFile->file_type,
                    'original_name' => $this->latestFile->original_name,
                    'file_size_kb' => $this->latestFile->file_size_kb,
                    'mime_type' => $this->latestFile->mime_type,
                    'uploaded_at' => $this->latestFile->uploaded_at->toISOString(),
                ];
            }),
            'all_files' => $this->when($this->relationLoaded('manuscriptFiles'), function () {
                return $this->manuscriptFiles->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'file_type' => $file->file_type,
                        'original_name' => $file->original_name,
                        'file_size_kb' => $file->file_size_kb,
                        'mime_type' => $file->mime_type,
                        'uploaded_at' => $file->uploaded_at->toISOString(),
                    ];
                });
            }),
            'can_upload_draft' => $this->canUploadDraft(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            '_links' => $this->links($request),
        ];
    }

    private function links(Request $request): array
    {
        $links = [
            'self' => "/api/manuscripts/{$this->id}",
        ];

        // Link download file terbaru
        if ($this->latestFile) {
            $links['download_latest'] = [
                'href' => "/api/manuscripts/{$this->id}/download",
                'method' => 'GET',
            ];
        }

        // Link upload/re-upload jika status memungkinkan
        if ($this->canUploadDraft()) {
            $linkMessage = $this->status === Manuscript::STATUS_INITIAL_DRAFT_REQUESTED
                ? 'Upload naskah awal Anda.'
                : 'Upload revisi naskah Anda.';

            $links['upload_draft'] = [
                'message' => $linkMessage,
                'href' => "/api/manuscripts/{$this->id}/upload-draft",
                'method' => 'POST',
            ];
        }

        // Link status history
        $links['status_history'] = [
            'href' => "/api/manuscripts/{$this->id}/status",
            'method' => 'GET',
        ];

        return $links;
    }
}
