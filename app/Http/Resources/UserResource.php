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
            'self' => '/api/auth/me',
            'logout' => [
                'href' => '/api/auth/logout',
                'method' => 'POST',
            ],
        ];

        // Role-based links
        $role = $this->role?->name;

        if ($role === 'admin') {
            $links['dashboard'] = '/api/admin/dashboard';
            $links['willingness_forms'] = '/api/willingness-forms';
            $links['create_user'] = [
                'href' => '/api/users',
                'method' => 'POST',
            ];
        } elseif ($role === 'penulis') {
            $links['upload_contract'] = [
                'href' => '/api/contracts',
                'method' => 'POST',
            ];
            $links['my_contract'] = '/api/contracts/me';
        } elseif ($role === 'reviewer') {
            $links['lorem'] = [
                'href' => '/api/lorem',
                'method' =>  'GET'
            ];
        } elseif ($role === 'penerbit') {
            $links['lorem'] = [
                'href' => '/api/lorem',
                'method' =>  'GET'
            ];
        }

        return $links;
    }
}
