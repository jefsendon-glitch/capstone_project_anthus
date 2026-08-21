<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates the default administrator account for this deployment.
     * Override via ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD before seeding.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@shauntiwater.com');
        $password = env('ADMIN_PASSWORD', 'ChangeMe123!');
        $resetPassword = filter_var(env('ADMIN_RESET_PASSWORD', false), FILTER_VALIDATE_BOOL);

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        // Existing accounts keep their password unless an intentional reset is requested.
        if ($resetPassword) {
            $admin->forceFill([
                'password' => Hash::make($password),
                'status' => 'active',
            ])->save();
        }

        $admin->syncRoles(['admin']);

        $this->command?->info("Default administrator is ready: {$email}");
    }
}
