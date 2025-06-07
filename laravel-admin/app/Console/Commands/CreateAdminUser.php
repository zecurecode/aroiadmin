<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create-user {username} {--password=AroMat1814}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        $password = $this->option('password');

        $user = User::create([
            'username' => $username,
            'name' => ucfirst($username),
            'password' => Hash::make($password),
            'siteid' => 0,
            'license' => 0,
            'role' => 'admin',
        ]);

        $this->info("Admin user '{$username}' created successfully!");

        return 0;
    }
}
