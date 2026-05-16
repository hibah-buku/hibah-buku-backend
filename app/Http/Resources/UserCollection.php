<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collection,
            'meta' => [
                'current_page' => $this->currentPage(),
                'total' => $this->total(),
                'per_page' => $this->perPage()
            ],
            '_links' => [
                'self' => url()->current(),
                'filter_by_role' => [
                    'message' => 'Query param role=reviewer|penerbit|admin',
                    'href' => '/api/users?role={role_name}',
                    'method' => 'GET'
                ]
            ]
        ];
    }
}
