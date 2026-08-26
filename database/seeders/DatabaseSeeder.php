<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            SupplierSeeder::class,
            AdminUserSeeder::class,
            PurchaseImportSeeder::class,
        ]);

        $this->command->info('Seeded successfully.');
        $this->command->info('Super Admin — '.env('ADMIN_EMAIL', 'admin@ulaman.test').' / '.env('ADMIN_PASSWORD', 'password'));
    }
}
