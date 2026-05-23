<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ManuscriptCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
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
                'all_manuscripts' => '/api/manuscripts',
                'draft_uploaded' => '/api/manuscripts?status=draft_uploaded',
                'under_review' => '/api/manuscripts?status=under_review',
                'revision_needed' => '/api/manuscripts?status=revision_needed',
                'approved' => '/api/manuscripts?status=approved',
            ],
        ];
    }
}
