<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $fileSize = Storage::disk('public')->exists($this->file_path)
            ? Storage::disk('public')->size($this->file_path)
            : 0;

        return [
            'id' => $this->id,
            'author' => [
                'id' => $this->author->id,
                'name' => $this->author->user->name,
                'email' => $this->author->user->email,
            ],
            'file_info' => [
                'file_path' => $this->file_path,
                'original_name' => $this->original_name,
                'size' => $fileSize, // Ukuran file dalam bytes
                'uploaded_at' => $this->created_at->toISOString(),
            ],
            'status' => $this->status,
            'validated_at' => $this->validated_at,
            'notes' => $this->when($request->user()?->role?->name === 'admin', $this->notes),

            '_links' => $this->links($request),
        ];
    }

    private function links(Request $request): array
    {
        $links = [
            'self' => "/api/contracts/{$this->id}",
            'download' => "/api/contracts/{$this->id}/download",
        ];

        // Link khusus Admin
        if ($request->user()?->role?->name === 'admin') {
            if ($this->status === 'contract_uploaded') {
                $links['validate'] = [
                    'message' => 'Endpoint Validasi Kontrak.',
                    'href' => "/api/contracts/{$this->id}/validate",
                    'method' => 'PATCH',
                ];
                $links['reject'] = [
                    'message' => 'Endpoint Penolakan Kontrak.',
                    'href' => "/api/contracts/{$this->id}/reject",
                    'method' => 'PATCH',
                ];
            }
        }
        // Link untuk semua orang (termasuk admin dan penulis)
        if ($this->status === 'contract_rejected') {
            $links['reupload'] = [
                'message' => 'Kontrak ditolak. Silakan unggah ulang versi perbaikan.',
                'href' => '/api/contracts',
                'method' => 'POST',
            ];
        } elseif ($this->status === 'contract_validated') {
            $links['next_step'] = [
                'message' => 'Kontrak valid. Anda dapat mengunggah naskah buku.',
                'href' => '/api/manuscripts', // endpoint next step, dikerjakan kelompok lain.
                'method' => 'POST'
            ];
        }
        return $links;
    }
}
