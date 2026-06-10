<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reviewer;
use App\Helpers\ApiResponse;

class ReviewerController extends Controller
{
    /**
     * Display a listing of the reviewers.
     * GET /api/reviewers
     */
    public function index()
    {
        $reviewers = Reviewer::orderBy('name')->get(['id as reviewer_id', 'name as reviewer_name', 'email as reviewer_email', 'user_id']);
        
        return ApiResponse::success('Daftar reviewer.', $reviewers);
    }
}
