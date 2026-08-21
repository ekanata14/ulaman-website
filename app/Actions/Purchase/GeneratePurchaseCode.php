<?php

namespace App\Actions\Purchase;

use App\Models\Purchase;
use Carbon\CarbonInterface;

/**
 * §10.2 — Kode nota `PB-{YYYYMM}-{4 digit}`, race-safe via lockForUpdate.
 * Wajib dipanggil di dalam transaksi (orchestrator).
 */
class GeneratePurchaseCode
{
    public function execute(CarbonInterface $tanggal): string
    {
        $prefix = 'PB-'.$tanggal->format('Ym').'-';

        $last = Purchase::withTrashed()
            ->where('kode', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('kode')
            ->value('kode');

        $seq = $last !== null ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
