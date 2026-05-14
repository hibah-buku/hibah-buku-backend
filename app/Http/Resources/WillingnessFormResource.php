<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WillingnessFormResource extends JsonResource
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
            'main_author' => [
                'name' => $this->main_author_name,
                'email' => $this->main_author_email,
                'institution' => $this->main_author_institution,
                'phone' => $this->main_author_phone,
            ],
            'co_authors' => $this->getCoAuthorsArray(),
            'book' => [
                'title' => $this->book_title,
                'type' => $this->book_type,
                'field_of_study' => $this->field_of_study,
                'abstract' => $this->book_abstract,
                'target_audience' => $this->target_audience,
            ],
            'status' => $this->status,
            'admin_notes' => $this->when($request->user()?->role?->name === 'admin', $this->admin_notes),
            'rejection_reason' => $this->when($this->status === 'rejected', $this->rejection_reason),
            'rejected_at'      => $this->when($this->status === 'rejected', $this->rejected_at?->toISOString()),
            'approved_user_id' => $this->when($this->status === 'approved', $this->users),
            'submitted_at' => $this->created_at->toISOString(),
            
            // HATEOAS Links
            '_links' => $this->links($request),
        ];
    }

     /**
     * Helper: Format co-authors menjadi array
     */
    private function getCoAuthorsArray(): array
    {
        $coAuthors = [];

        // Co-Author 1 (Wajib)
        if ($this->co_author_1_name) {
            $coAuthors[] = [
                'name' => $this->co_author_1_name,
                'email' => $this->co_author_1_email,
                'institution' => $this->co_author_1_institution,
            ];
        }

        // Co-Authors 2-4 (Opsional)
        for ($i = 2; $i <= 4; $i++) {
            $name = $this->{"co_author_{$i}_name"};
            if ($name) {
                $coAuthors[] = [
                    'name' => $name,
                    'email' => $this->{"co_author_{$i}_email"},
                    'institution' => $this->{"co_author_{$i}_institution"},
                ];
            }
        }

        return $coAuthors;
    }

    /**
     * Generate HATEOAS Links berdasarkan konteks request
     */
    private function links(Request $request): array
    {
        $links = [
            'self' => "/api/willingness-forms/{$this->id}",
        ];

        // Jika admin, tampilkan link management
        if ($request->user()?->role?->name === 'admin') {
            $links['approve'] = [
                'href' => "/api/willingness-forms/{$this->id}/approve",
                'method' => 'PATCH',
            ];
            $links['reject'] = [
                'href' => "/api/willingness-forms/{$this->id}/reject",
                'method' => 'PATCH',
            ];
        }

        // Jika approved, tampilkan link next step
        if ($this->status === 'approved') {
            $links['next_step'] = [
                'message' => 'Form sudah disetujui. Akun penulis akan segera dibuat.',
                'check_account' => '/api/auth/me',
            ];
        }

        if ($this->status === 'rejected') {
        $links['next_step'] = [
            'message' => 'Form ditolak. Pemohon dapat mengajukan ulang.',
        ];
    }

        return $links;
    }
}
