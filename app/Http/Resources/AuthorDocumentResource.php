<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AuthorDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $documentLabels = [
            'ktp' => 'Kartu Tanda Penduduk (KTP)',
            'surat_pernyataan' => 'Surat Pernyataan Penulis',
            'rekening' => 'Buku Rekening Bank',
        ];

        $label = $documentLabels[$this->document_type] ?? ucwords(str_replace('_', ' ', $this->document_type));

        return [
            'id' => $this->id,
            'manuscript_id' => $this->manuscript_id,
            'document_type' => $this->document_type,
            'document_type_label' => $label,
            'file_url' => Storage::disk('public')->exists($this->file_path) ? Storage::url($this->file_path) : null,
            'file_path' => $this->file_path,
            'file_size_kb' => $this->file_size_kb,
            'is_verified' => (bool)$this->is_verified,
            'uploaded_at' => $this->uploaded_at ? $this->uploaded_at->toISOString() : null,
            '_links' => [
                'self' => "/api/manuscripts/me/documents",
                'delete' => "/api/manuscripts/me/documents/{$this->document_type}",
            ]
        ];
    }
}
