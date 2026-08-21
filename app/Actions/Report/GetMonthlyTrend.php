<?php

namespace App\Actions\Report;

use App\Actions\Report\Concerns\AppliesPurchaseFilters;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Models\Purchase;
use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * §F-07 — Tren belanja bulanan (hanya nota final). Untuk lintas-DB (MySQL app +
 * SQLite test) TIDAK memakai DATE_FORMAT: baris diambil lalu dikelompokkan di
 * PHP per bulan `tanggal`, total dijumlah dengan bcadd (scale 2, string).
 */
class GetMonthlyTrend
{
    use AppliesPurchaseFilters;

    /**
     * @return array<int, array{month: string, label: string, total: string}>
     */
    public function execute(PurchaseFilterData $f): array
    {
        $query = Purchase::query()->final();
        $this->applyPurchaseFilters($query, $f);

        $rows = $query
            ->orderBy('tanggal')
            ->get(['id', 'tanggal', 'grand_total']);

        /** @var array<string, string> $totals */
        $totals = [];

        foreach ($rows as $row) {
            $key = $row->tanggal->format('Y-m');
            $current = $totals[$key] ?? Money::zero();
            $totals[$key] = Money::add($current, (string) $row->grand_total);
        }

        ksort($totals);

        $result = [];
        foreach ($totals as $month => $total) {
            $result[] = [
                'month' => $month,
                'label' => CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->format('M Y'),
                'total' => $total,
            ];
        }

        return $result;
    }
}
