<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class RoleAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles
        $roles = ['admin', 'penulis', 'reviewer', 'penerbit'];

        foreach ($roles as $roleName) {
            Role::create([
                'name' => $roleName
            ]);
        }

        // Create default Admin User
        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            User::create([
                'name' => 'SuperAdmin',
                'email' => 'admin@hibahbuku.ac.id',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
                'status' => 'active'
            ]);
        }
    }
}
