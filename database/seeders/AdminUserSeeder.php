<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Super admin awal, kredensial dari .env (§18 PRD).
     */
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@ulaman.test');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) env('ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make((string) env('ADMIN_PASSWORD', 'password')),
                'role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
