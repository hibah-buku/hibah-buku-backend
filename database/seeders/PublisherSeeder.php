<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PublisherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $publisherRole = Role::where('name', 'penerbit')->first();

        if ($publisherRole) {
            // Check if the user already exists to prevent duplicates
            $exists = User::where('email', 'penerbit@hibahbuku.ac.id')->exists();

            if (!$exists) {
                User::create([
                    'name' => 'Penerbit Hibah Buku',
                    'email' => 'penerbit@hibahbuku.ac.id',
                    'password' => Hash::make('password123'),
                    'role_id' => $publisherRole->id,
                    'status' => 'active'
                ]);
            }
        }
    }
}
