<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update admin user
        User::where('username', 'admin')->update(['role' => 'admin']);

        // Update all other users to 'user' role
        User::where('username', '!=', 'admin')->update(['role' => 'user']);

        echo "User roles updated successfully!\n";
        echo 'Admin users: '.User::where('role', 'admin')->count()."\n";
        echo 'Regular users: '.User::where('role', 'user')->count()."\n";
    }
}
