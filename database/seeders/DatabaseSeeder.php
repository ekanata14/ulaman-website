<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@gretiva.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);

        // Sample regular users
        User::factory(10)->create();

        $this->command->info('Seeded successfully.');
        $this->command->info('Super Admin — admin@gretiva.com / password');
    }
}
