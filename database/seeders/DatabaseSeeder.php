<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndAdminSeeder::class,
        ]);

        // Tambahkan pengguna uji tambahan jika diperlukan.
        $adminRole = Role::where('name', 'admin')->first();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'role_id' => $adminRole ? $adminRole->id : null,
            ]
        );
    }
}
