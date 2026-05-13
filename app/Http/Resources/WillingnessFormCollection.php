<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WillingnessFormCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
            'data' => $this->collection, // pakai WillingnessFormResource
            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'total' => $this->total(),
                'per_page' => $this->perPage(),
            ],
            '_links' => [
                'self' => url()->current(),
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'next' => $this->nextPageUrl(),
                'prev' => $this->previousPageUrl(),
                'all_forms' => '/api/willingness-forms',
                'pending_forms' => '/api/willingness-forms?status=pending',
                'approved_forms' => '/api/willingness-forms?status=approved',
            ],
        ];
    }
}
