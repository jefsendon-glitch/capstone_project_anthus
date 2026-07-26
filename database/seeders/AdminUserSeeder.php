<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates (or updates) the single default administrator account for this deployment.
     * Override via ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD in .env before seeding a real environment.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@shauntiwater.com');
        $password = env('ADMIN_PASSWORD', 'ChangeMe123!');

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['admin']);

        $this->command?->warn("Default admin ready -> {$email} / {$password} — change this password immediately after first login.");
    }
}
