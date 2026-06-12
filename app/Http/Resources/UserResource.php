<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Reviewer;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role?->name,
            'status' => $this->deleted_at ? 'inactive' : ($this->status ?? 'active'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            '_links' => $this->links($request),
        ];

        if ($this->role?->name === 'reviewer') {
            $reviewer = Reviewer::where('user_id', $this->id)->first();
            if ($reviewer) {
                $data['reviewer_id'] = $reviewer->id;
            }
        }

        return $data;
    }

    private function links(Request $request): array
    {
        $links = [
            'self' => "/api/users/{$this->id}",
        ];

        if ($request->user() && $request->user()->id === $this->id) {
            $links['logout'] = [
                'message' => ' logout',
                'href' => '/api/auth/logout',
                'method' => 'POST',
            ];
        }

        // Role-based links
        $role = $this->role?->name;

        if ($role === 'admin') {
            $links['willingness_forms'] = [
                'message' => 'Manajemen form kesediaan calon penulis',
                'href' => '/api/willingness-forms',
                'method' => 'GET'
            ];

            $links['contracts'] = [
                'message' => 'Manajemen Kontrak penulis',
                'href' => '/api/contracts',
                'method' => 'GET'
            ];

            $links['manage_users'] = [
                'message' => 'Manajemen User',
                'href' => '/api/users',
                'method' => 'GET'
            ];
        } elseif ($role === 'penulis') {
            $links['upload_contract'] = [
                'message' => 'Upload kontrak',
                'href' => '/api/contracts',
                'method' => 'POST',
            ];

            $links['view_my_contract'] = [
                'message' => 'Lihat status dan detail kontrak Anda.',
                'href' => '/api/contracts/me',
                'method' => 'GET',
            ];

            if ($this->author && $this->author->contracts()->exists()) {
                $contractId = $this->author->contracts()->latest()->first()->id;

                $links['download_contract'] = [
                    'message' => 'Unduh file kontrak Anda.',
                    'href' => "/api/contracts/{$contractId}/download",
                    'method' => 'GET',
                ];
            }
        } elseif ($role === 'reviewer') {
            $reviewerId = $this->id;
            $reviewer = Reviewer::where('user_id', $this->id)->first();
            if ($reviewer) {
                $reviewerId = $reviewer->id;
            }
            $links['assignments'] = [
                'message' => 'Daftar tugas review untuk reviewer',
                'href' => "/api/reviewers/{$reviewerId}/assignments",
                'method' => 'GET'
            ];
            $links['rubrics'] = [
                'message' => 'Daftar rubrik penilaian review',
                'href' => '/api/rubrics',
                'method' => 'GET'
            ];
        } elseif ($role === 'penerbit') {
            $links['publisher_dashboard'] = [
                'message' => 'Pantau naskah pra-cetak.',
                'href' => '/api/publisher/dashboard',
                'method' => 'GET',
            ];

            $links['publisher_manuscripts'] = [
                'message' => 'Daftar naskah pra-cetak untuk ditinjau.',
                'href' => '/api/publisher/manuscripts',
                'method' => 'GET',
            ];

            $links['publisher_decision'] = [
                'message' => 'Buat keputusan penerbitan naskah.',
                'href' => '/api/publisher/manuscripts/{manuscript_id}/decision',
                'method' => 'POST',
            ];
        }

        return $links;
    }
}
