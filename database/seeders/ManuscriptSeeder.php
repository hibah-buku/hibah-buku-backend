<?php

namespace Database\Seeders;

use App\Models\Manuscript;
use App\Models\ManuscriptFile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ManuscriptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->info('No users found, skipping ManuscriptSeeder.');
            return;
        }

        // Create a few sample manuscripts with one uploaded draft file each
        for ($i = 1; $i <= 3; $i++) {
            $manuscript = Manuscript::create([
                'user_id' => $user->id,
                'contract_id' => null,
                'title' => "Sample Manuscript {$i}",
                'book_type' => ($i % 2) ? 'bukuajar' : 'bukureferensi',
                'status' => Manuscript::STATUS_DRAFT_UPLOADED,
                'deadline_draft' => Carbon::now()->addWeeks(2)->toDateString(),
                'deadline_revision' => Carbon::now()->addWeeks(4)->toDateString(),
            ]);

            ManuscriptFile::create([
                'manuscript_id' => $manuscript->id,
                'file_type' => 'draft_awal',
                'file_path' => "manuscripts/{$manuscript->id}/draft_{$i}.pdf",
                'original_name' => "draft_{$i}.pdf",
                'file_size_kb' => 256,
                'mime_type' => 'application/pdf',
                'uploaded_at' => Carbon::now(),
            ]);
        }
    }
}
