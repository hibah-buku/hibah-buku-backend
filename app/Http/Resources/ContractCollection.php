<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ContractCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection, // pakai ContractResource
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
                'all_contract' => '/api/contracts',
                'uploaded_contracts' => '/api/contracts?status=contract_uploaded',
                'validated_contracts' => '/api/contracts?status=contract_validated',
                'rejected_contracts' => '/api/contracts?status=contract_rejected',
            ],
        ];
    }
}
