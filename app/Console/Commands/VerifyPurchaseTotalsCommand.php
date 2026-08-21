<?php

namespace App\Console\Commands;

use App\Actions\Maintenance\VerifyPurchaseTotals;
use Illuminate\Console\Command;

class VerifyPurchaseTotalsCommand extends Command
{
    protected $signature = 'purchase:verify-totals {--fix : Perbaiki total header dari agregat baris item}';

    protected $description = 'Verifikasi invariant §7: Σ net_item == grand_total pada semua nota.';

    public function handle(VerifyPurchaseTotals $action): int
    {
        $fix = (bool) $this->option('fix');
        $report = $action->execute($fix);

        $this->info("Diperiksa: {$report['checked']} nota.");

        if ($report['mismatches'] === []) {
            $this->info('✔ Semua nota konsisten dengan invariant §7.');

            return self::SUCCESS;
        }

        $this->error(count($report['mismatches']).' nota tidak konsisten:');
        $this->table(
            ['Kode', 'grand_total', 'Σ net_item'],
            array_map(
                static fn (array $m): array => [$m['kode'], $m['grand_total'], $m['sum_net']],
                $report['mismatches'],
            ),
        );

        if ($fix) {
            $this->info("Diperbaiki: {$report['fixed']} nota.");

            return self::SUCCESS;
        }

        $this->warn('Jalankan dengan --fix untuk merekonstruksi total header.');

        return self::FAILURE;
    }
}
