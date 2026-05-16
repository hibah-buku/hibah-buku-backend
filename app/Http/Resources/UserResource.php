<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->whenLoaded('role', fn() => $this->role->name),
            '_links' => $this->links($request),
        ];
    }

     private function links(Request $request): array
    {
        $links = [
            'self' => "/api/users/{$this->id}",
        ];

        if ($request->user()->id === $this->id) {
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
                'href' =>'/api/willingness-forms',
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
        } elseif ($role === 'reviewer') { // Tugas kelompok lain
            $links['lorem'] = [
                'message' => 'lorem ipsum dolor sit amet',
                'href' => '/api/lorem',
                'method' =>  'GET'
            ];
        } elseif ($role === 'penerbit') { // Tugas kelompok lain
            $links['lorem'] = [
                'message' => 'lorem ipsum dolor sit amet',
                'href' => '/api/lorem',
                'method' =>  'GET'
            ];
        }

        return $links;
    }
}
