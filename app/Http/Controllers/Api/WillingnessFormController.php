<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Models\WillingnessForm;
use Illuminate\Support\Facades\Validator;

class WillingnessFormController extends Controller
{
    /**
     * UC-02: Submit Form Kesediaan Penulis
     * Endpoint: POST /api/auth/register-willingness
     */
    public function store(Request $request)
    {
    }
}
