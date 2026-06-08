<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Reviewer;
use App\Models\Author;
use App\Models\Manuscript;
use App\Models\ManuscriptFile;
use App\Models\ReviewRubric;
use Illuminate\Support\Facades\Hash;

class ReviewerModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Review Rubrics
        $rubrics = [
            ['criteria_name' => 'Kesesuaian Format', 'max_score' => 20, 'applicable_book_type' => 'Both'],
            ['criteria_name' => 'Kedalaman Materi', 'max_score' => 40, 'applicable_book_type' => 'Both'],
            ['criteria_name' => 'Tata Bahasa dan Diksi', 'max_score' => 20, 'applicable_book_type' => 'Both'],
            ['criteria_name' => 'Kemutakhiran Referensi', 'max_score' => 20, 'applicable_book_type' => 'Both'],
        ];

        foreach ($rubrics as $rubric) {
            ReviewRubric::firstOrCreate(['criteria_name' => $rubric['criteria_name']], $rubric);
        }

        // 2. Create Users for Author and Reviewer
        $rolePenulis = Role::where('name', 'penulis')->first();
        $roleReviewer = Role::where('name', 'reviewer')->first();

        // Create Author User
        $authorUser = User::firstOrCreate(
            ['email' => 'penulis@hibahbuku.ac.id'],
            [
                'name' => 'Dr. Penulis Handal',
                'password' => Hash::make('password123'),
                'role_id' => $rolePenulis->id,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $author = Author::firstOrCreate(
            ['user_id' => $authorUser->id],
            [
                'institution' => 'Universitas Contoh',
                'field_of_study' => 'Ilmu Komputer',
            ]
        );

        // Create Reviewer Users
        for ($i = 1; $i <= 3; $i++) {
            $reviewerUser = User::firstOrCreate(
                ['email' => "reviewer{$i}@hibahbuku.ac.id"],
                [
                    'name' => "Prof. Reviewer {$i}",
                    'password' => Hash::make('password123'),
                    'role_id' => $roleReviewer->id,
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            Reviewer::firstOrCreate(
                ['user_id' => $reviewerUser->id],
                [
                    'name' => $reviewerUser->name,
                    'email' => $reviewerUser->email,
                ]
            );
        }

        // 3. Create Dummy Manuscripts ready for assignment
        for ($i = 1; $i <= 5; $i++) {
            $manuscript = Manuscript::firstOrCreate(
                ['title' => "Buku Ajar Pemrograman Web Lanjut Edisi {$i}"],
                [
                    'user_id' => $authorUser->id,
                    'contract_id' => null, // Optional
                    'book_type' => 'bukuajar',
                    'status' => 'draft_uploaded',
                    'deadline_draft' => now()->addDays(30),
                ]
            );

            // Add dummy file
            ManuscriptFile::firstOrCreate(
                ['manuscript_id' => $manuscript->id, 'file_type' => 'draft_awal'],
                [
                    'file_path' => "manuscripts/dummy_draft_{$i}.pdf",
                    'original_name' => "draft_awal_{$i}.pdf",
                    'file_size_kb' => 1024,
                    'mime_type' => 'application/pdf',
                    'uploaded_at' => now(),
                ]
            );
        }
    }
}
