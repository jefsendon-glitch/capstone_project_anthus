<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Baseline seed for every environment: roles, business settings, and the one
     * real admin account. Sample staff/customers/products only seed in local via DemoDataSeeder.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        Setting::current()->update([
            'business_name' => 'Shaunti Water Refilling Station',
            'address' => 'San Roque, Bato, Camarines Sur, Philippines',
            'contact_number' => '+63 912 345 6789',
            'contact_email' => 'shauntiwater@gmail.com',
        ]);

        $this->call(AdminUserSeeder::class);

        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
