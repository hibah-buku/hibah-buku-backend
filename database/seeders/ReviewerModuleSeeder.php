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
        $rubricsAjar = [
            'Deskripsi Capaian Pembelajaran (CP)',
            'Kesesuaian materi yang dikembangkan dengan CP',
            'Buku Ajar yang dikembangkan dapat membantu dicapainya CP',
            'Organisasi dan keruntutan Isi Buku Ajar',
            'Kemutakhiran isi materi',
            'Keakuratan materi secara keseluruhan',
            'Ketepatan urutan penyajian materi',
            'Kedalaman materi sesuai dengan target pengguna',
            'Kesesuaian materi dengan kebutuhan target pengguna',
            'Tata tulis (writing mechanics)',
            'Kejelasan bahasa yang digunakan',
            'Ketepatan gambar/ tabel/ grafik dalam memperjelas isi materi',
            'Kemampuan media untuk memacu motivasi siswa dalam belajar',
            'Rujukan yang digunakan mutakhir dan sesuai',
        ];

        foreach ($rubricsAjar as $rubric) {
            ReviewRubric::create([
                'criteria_name' => $rubric,
                'max_score' => 5,
                'applicable_book_type' => 'Buku Ajar',
            ]);
        }

        $rubricsReferensi = [
            'Kesesuaian topik, cakupan, dan kedalaman materi dengan kebutuhan kajian atau mata kuliah',
            'Informasi bebas dari kesalahan fakta, diperkuat dengan teori dan data yang valid',
            'Terbitan terbaru atau memuat informasi terkini sesuai perkembangan ilmu',
            'Latar belakang penulis sesuai bidang keilmuan dan diakui reputasinya (akademisi, peneliti, praktisi)',
            'Materi tersusun sistematis, logis, dan mudah diikuti alurnya',
            'Menggunakan bahasa akademik yang jelas, formal, tidak ambigu, serta konsisten dalam istilah',
            'Memuat daftar pustaka yang memadai, relevan, dan sesuai standar sitasi ilmiah',
            'Isi tidak bias, tidak berorientasi komersial, dan bebas dari pandangan subjektif berlebihan',
            'Adanya gambar, tabel, grafik, atau contoh nyata yang membantu pemahaman',
            'Tidak hanya deskriptif, tetapi juga memberikan penjelasan kritis, analisis, dan sintesis',
            'Tingkat kesulitan sesuai target pembaca (misalnya mahasiswa S1, S2, atau peneliti)',
            'Menggunakan istilah secara konsisten dan sesuai standar disiplin ilmu',
            'Buku dapat menjadi acuan utama, praktis digunakan untuk studi, penelitian, maupun pengajaran',
        ];

        foreach ($rubricsReferensi as $rubric) {
            ReviewRubric::create([
                'criteria_name' => $rubric,
                'max_score' => 5,
                'applicable_book_type' => 'Buku Referensi',
            ]);
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
        for ($i = 1; $i <= 3; $i++) {
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
                    'file_path' => "manuscripts/dummy_draft_ajar_{$i}.pdf",
                    'original_name' => "draft_awal_ajar_{$i}.pdf",
                    'file_size_kb' => 1024,
                    'mime_type' => 'application/pdf',
                    'uploaded_at' => now(),
                ]
            );
        }

        for ($i = 1; $i <= 2; $i++) {
            $manuscriptRef = Manuscript::firstOrCreate(
                ['title' => "Buku Referensi Kecerdasan Buatan Terapan Edisi {$i}"],
                [
                    'user_id' => $authorUser->id,
                    'contract_id' => null, // Optional
                    'book_type' => 'bukureferensi',
                    'status' => 'draft_uploaded',
                    'deadline_draft' => now()->addDays(30),
                ]
            );

            // Add dummy file
            ManuscriptFile::firstOrCreate(
                ['manuscript_id' => $manuscriptRef->id, 'file_type' => 'draft_awal'],
                [
                    'file_path' => "manuscripts/dummy_draft_ref_{$i}.pdf",
                    'original_name' => "draft_awal_ref_{$i}.pdf",
                    'file_size_kb' => 2048,
                    'mime_type' => 'application/pdf',
                    'uploaded_at' => now(),
                ]
            );
        }
    }
}
