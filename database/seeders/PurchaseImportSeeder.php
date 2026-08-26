<?php

namespace Database\Seeders;

use App\Actions\Import\ImportPurchaseExcel;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder data nota "Ulaman Renovation" (Jan–Ags 2026) dari berkas Excel
 * bawaan repo. TIDAK menghitung/parsing sendiri — mendelegasikan sepenuhnya
 * ke pipeline impor aplikasi (ImportPurchaseExcel → ParsePurchaseExcel +
 * StorePurchase) agar hasil seed identik dengan Import Wizard.
 *
 * Idempotent: jika sudah ada nota, impor dilewati (hindari duplikasi kode).
 */
class PurchaseImportSeeder extends Seeder
{
    public function run(): void
    {
        if (Purchase::query()->exists()) {
            $this->command?->warn('Nota pembelian sudah ada — melewati PurchaseImportSeeder. '
                .'Jalankan "migrate:fresh --seed" untuk impor ulang dari nol.');

            return;
        }

        $path = database_path('data/ulaman-renovation.xlsx');
        if (! is_file($path)) {
            $this->command?->error("Berkas sumber tidak ditemukan: {$path}");

            return;
        }

        $actor = User::query()->where('role', 'super_admin')->first()
            ?? User::query()->first();

        if ($actor === null) {
            $this->command?->error('Tidak ada user untuk dijadikan aktor impor. '
                .'Pastikan AdminUserSeeder berjalan lebih dulu.');

            return;
        }

        $this->command?->info('Mengimpor data Ulaman Renovation…');

        // Jalankan job impor secara sinkron (pipeline yang sama dengan Import Wizard).
        ImportPurchaseExcel::dispatchSync($path, (int) $actor->getKey());

        $this->command?->info(sprintf(
            'Impor selesai: %s nota, %s item, %s supplier.',
            number_format(Purchase::query()->count(), 0, ',', '.'),
            number_format(PurchaseItem::query()->count(), 0, ',', '.'),
            number_format(Supplier::query()->count(), 0, ',', '.'),
        ));
    }
}
