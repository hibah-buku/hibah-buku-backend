<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WillingnessForm;

class WillingnessFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WillingnessForm::create([
            'main_author_name' => 'Budi Santoso',
            'main_author_email' => 'budi.santoso@example.com',
            'main_author_institution' => 'Universitas Negeri Jakarta',
            'main_author_phone' => '081234567890',

            'co_author_1_name' => 'Siti Aminah',
            'co_author_1_email' => 'siti.aminah@example.com',
            'co_author_1_institution' => 'Universitas Negeri Jakarta',

            'co_author_2_name' => 'Agus Pratama',
            'co_author_2_email' => 'agus.pratama@example.com',
            'co_author_2_institution' => 'Institut Teknologi Bandung',

            'co_author_3_name' => null,
            'co_author_3_email' => null,
            'co_author_3_institution' => null,

            'co_author_4_name' => null,
            'co_author_4_email' => null,
            'co_author_4_institution' => null,

            'book_title' => 'Metodologi Pengajaran Matematika untuk Pendidikan Tinggi',
            'book_type' => 'bukuajar',
            'field_of_study' => 'Pendidikan Matematika',
            'book_abstract' => 'Buku ini membahas strategi pembelajaran matematika di perguruan tinggi, dengan fokus pada kurikulum berbasis kompetensi dan aplikasi praktis.',
            'target_audience' => 'Dosen, mahasiswa, dan pengembang kurikulum pendidikan matematika',
            'status' => 'pending',
            'admin_notes' => null,
            'rejection_reason' => null,
            'rejected_at' => null,
        ]);

        WillingnessForm::create([
            'main_author_name' => 'Dewi Anggraeni',
            'main_author_email' => 'dewi.anggraeni@example.com',
            'main_author_institution' => 'Universitas Airlangga',
            'main_author_phone' => '082198765432',

            'co_author_1_name' => 'Rina Putri',
            'co_author_1_email' => 'rina.putri@example.com',
            'co_author_1_institution' => 'Universitas Airlangga',

            'co_author_2_name' => null,
            'co_author_2_email' => null,
            'co_author_2_institution' => null,

            'co_author_3_name' => null,
            'co_author_3_email' => null,
            'co_author_3_institution' => null,

            'co_author_4_name' => null,
            'co_author_4_email' => null,
            'co_author_4_institution' => null,

            'book_title' => 'Ensiklopedia Referensi Ilmu Administrasi Publik',
            'book_type' => 'bukureferensi',
            'field_of_study' => 'Administrasi Publik',
            'book_abstract' => 'Referensi komprehensif untuk ilmu administrasi publik dengan teori, studi kasus, dan kebijakan terbaru.',
            'target_audience' => 'Akademisi, peneliti, dan praktisi administrasi publik',
            'status' => 'approved',
            'admin_notes' => 'Disetujui dan akun penulis telah dibuat otomatis.',
            'rejection_reason' => null,
            'rejected_at' => null,
        ]);
    }
}
