<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\ReviewRubric;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewRubricController extends Controller
{
    public function index(Request $request)
    {
        $query = ReviewRubric::query();

        if ($request->has('book_type')) {
            $bookType = $request->query('book_type'); // 'bukuajar' or 'bukureferensi'
            
            // Map frontend's book_type to database applicable_book_type enum
            $mappedType = null;
            if ($bookType === 'bukuajar') {
                $mappedType = 'Buku Ajar';
            } elseif ($bookType === 'bukureferensi') {
                $mappedType = 'Buku Referensi';
            }

            if ($mappedType) {
                $query->whereIn('applicable_book_type', [$mappedType, 'Both']);
            }
        }

        $rubrics = $query->orderBy('id')->get(["id", "criteria_name", "max_score", "applicable_book_type"]);

        return ApiResponse::success('Daftar rubrik penilaian.', $rubrics);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'criteria_name' => 'required|string|max:255',
            'max_score' => 'required|integer|min:1',
            'applicable_book_type' => 'nullable|in:Buku Ajar,Buku Referensi,Both',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        $rubric = ReviewRubric::create([
            'criteria_name' => $request->criteria_name,
            'max_score' => $request->max_score,
            'applicable_book_type' => $request->input('applicable_book_type', 'Both'),
        ]);

        return ApiResponse::success('Rubrik berhasil ditambahkan.', $rubric, 201);
    }

    public function update(Request $request, ReviewRubric $rubric)
    {
        $validator = Validator::make($request->all(), [
            'criteria_name' => 'required|string|max:255',
            'max_score' => 'required|integer|min:1',
            'applicable_book_type' => 'nullable|in:Buku Ajar,Buku Referensi,Both',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        $rubric->update([
            'criteria_name' => $request->criteria_name,
            'max_score' => $request->max_score,
            'applicable_book_type' => $request->input('applicable_book_type', $rubric->applicable_book_type),
        ]);

        return ApiResponse::success('Rubrik berhasil diubah.', $rubric);
    }

    public function destroy(ReviewRubric $rubric)
    {
        $rubric->delete();
        return ApiResponse::success('Rubrik berhasil dihapus.', null);
    }
}
