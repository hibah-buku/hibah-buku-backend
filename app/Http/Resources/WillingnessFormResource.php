<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class WillingnessFormResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $linkedUser = null;
        if ($this->status === 'approved') {
            $email = trim($this->main_author_email);
            $linkedUser = User::Where('email', $email)->first();
        }

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
            // Menampilkan data akun hanya jika sudah diproses dan requester adalah Admin
            'created_account' => $this->when($this->status === 'approved' && $linkedUser, function () use ($linkedUser) {
                return [
                    'user_id' => $linkedUser->id,
                    'author_id' => $linkedUser->author ? $linkedUser->author->id : null,
                    'role' => 'penulis',
                    'temporary_password' => $this->temporary_password ?? 'Sent to Email',
                ];
            }),

            'admin_notes' => $this->when($request->user()?->role?->name === 'admin', $this->admin_notes),
            'rejection_reason' => $this->when($this->status === 'rejected', $this->rejection_reason),
            'rejected_at'      => $this->when($this->status === 'rejected', $this->rejected_at?->toISOString()),
            // 'approved_user_id' => $this->when($this->status === 'approved', $this->users),
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
            if ($this->status === 'pending') {
                $links['approve'] = [
                    'message' => 'Endpoint Persetujuan Kesediaan.',
                    'href' => "/api/willingness-forms/{$this->id}/approve",
                    'method' => 'PATCH',
                ];
                $links['reject'] = [
                    'message' => 'Endpoint Penolakan Kesediaan',
                    'href' => "/api/willingness-forms/{$this->id}/reject",
                    'method' => 'PATCH',
                ];
            } elseif ($this->status === 'approved') {
                $linkedUser = User::where('email', $this->main_author_email)->first();
                if ($linkedUser) {
                    $links['view_user_profile'] = "/api/users/{$linkedUser->id}";
                    $links['view_author_profile'] = "/api/authors/{$linkedUser->author?->id}";
                    $links['next_step'] = [
                        'message' => 'Akun penulis telah aktif. Silakan pantau progress penulis.',
                        'href' => '/api/contracts?author_id=' . ($linkedUser->author ? $linkedUser->author->id : ''),
                        'method' => 'GET'
                    ];
                }
            }
        } elseif ($this->status === 'approved') { // NON-ADMIN
            $links['next_step'] = [
                'message' => 'Form Kesediaan sudah disetujui. Penulis dapat login dan mengunggah kontrak.',
                'href' => '/api/contracts',
                'method' => 'POST'
            ];
        }

        // JIka reject, menampilkan link registrasi ulang
        if ($this->status === 'rejected') {
        $links['submit_new_proposal'] = [
            'message' => 'Form ditolak. Pemohon dapat mengajukan ulang setelah melakukan perbaikan.',
            'href' => '/api/auth/register-willingness',
            'method' => 'POST',
        ];
    }

        return $links;
    }
}
