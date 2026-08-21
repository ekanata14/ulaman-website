<?php

namespace App\Console\Commands;

use App\Actions\Import\ImportPurchaseExcel;
use App\Models\Purchase;
use App\Models\PurchaseBundle;
use App\Models\PurchaseItem;
use App\Models\PurchasePhoto;
use App\Models\User;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * §F-09 — Impor sumber Excel ke nota. Menjalankan job impor secara sinkron lalu
 * mencetak tabel rekonsiliasi (sistem vs Excel) per sheet + grand total.
 */
class ImportPurchasesCommand extends Command
{
    protected $signature = 'purchase:import {path : Path berkas .xlsx} {--actor= : ID user aktor} {--fresh : Hapus nota lama sebelum impor}';

    protected $description = 'Impor pembelian dari Excel via StorePurchase, lalu rekonsiliasi total.';

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->error("Berkas tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $actor = $this->resolveActor();
        if ($actor === null) {
            $this->error('Aktor tidak ditemukan. Berikan --actor=<id> atau seed super_admin.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->purgeExisting();
            $this->info('Data nota lama dihapus (--fresh).');
        }

        $this->info("Mengimpor {$path} sebagai aktor #{$actor->getKey()} ({$actor->email})...");

        ImportPurchaseExcel::dispatchSync($path, (int) $actor->getKey());

        $recon = Cache::get('import:reconciliation');
        if (! is_array($recon)) {
            $this->error('Rekonsiliasi tidak tersedia setelah impor.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Rekonsiliasi (system vs excel):');
        $this->table(
            ['Sheet', 'System', 'Excel', 'Delta (excel-system)'],
            array_map(
                static fn (array $row): array => [
                    $row['sheet'],
                    Money::format($row['system']),
                    Money::format($row['excel']),
                    Money::format($row['delta']),
                ],
                $recon['rows'],
            ),
        );

        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Grand total SYSTEM', Money::format($recon['totalSystem']).' ('.$recon['totalSystem'].')'],
                ['Grand total EXCEL', Money::format($recon['totalExcel']).' ('.$recon['totalExcel'].')'],
                ['Total delta (excel-system)', Money::format($recon['totalDelta']).' ('.$recon['totalDelta'].')'],
                ['Total nota', (string) $recon['stats']['notas']],
                ['Total item rows (parsed)', (string) $recon['stats']['items']],
                ['PurchaseItem::count()', (string) PurchaseItem::count()],
            ],
        );

        $warnings = is_array($recon['warnings']) ? $recon['warnings'] : [];
        if ($warnings !== []) {
            $this->newLine();
            $this->warn(count($warnings).' peringatan (needs_review / anomali):');
            foreach ($warnings as $w) {
                $this->line('  - '.$w);
            }
        }

        return self::SUCCESS;
    }

    private function resolveActor(): ?User
    {
        $id = $this->option('actor');
        if ($id !== null) {
            return User::find((int) $id);
        }

        return User::where('role', 'super_admin')->orderBy('id')->first();
    }

    private function purgeExisting(): void
    {
        DB::transaction(static function (): void {
            PurchasePhoto::query()->delete();
            PurchaseBundle::query()->delete();
            PurchaseItem::query()->delete();
            Purchase::withTrashed()->forceDelete();
        });
    }
}
