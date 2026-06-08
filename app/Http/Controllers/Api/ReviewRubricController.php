<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\ReviewRubric;

class ReviewRubricController extends Controller
{
    public function index()
    {
        $rubrics = ReviewRubric::orderBy('id')->get(["id", "criteria_name", "max_score", "applicable_book_type"]);

        return ApiResponse::success('Daftar rubrik penilaian.', $rubrics);
    }
}
