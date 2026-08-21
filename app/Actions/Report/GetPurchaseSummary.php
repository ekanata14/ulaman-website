<?php

namespace App\Actions\Report;

use App\Actions\Report\Concerns\AppliesPurchaseFilters;
use App\DTOs\Purchase\PurchaseFilterData;
use App\DTOs\Report\SummaryData;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * §10.6 / §F-06.2 — Ringkasan (total, jumlah nota/item/supplier, rata-rata per
 * bulan) untuk kartu ringkasan guest. Hanya nota final.
 */
class GetPurchaseSummary
{
    use AppliesPurchaseFilters;

    public function execute(PurchaseFilterData $f): SummaryData
    {
        $base = Purchase::query()->final();
        $this->applyPurchaseFilters($base, $f);

        $ids = (clone $base)->pluck('id');
        $total = Money::add((string) (clone $base)->sum('grand_total'), '0');
        $itemCount = PurchaseItem::query()->whereIn('purchase_id', $ids)->count();
        $supplierCount = (clone $base)->whereNotNull('supplier_id')->distinct()->count('supplier_id');

        $min = (clone $base)->min('tanggal');
        $max = (clone $base)->max('tanggal');
        $months = 1;
        if ($min !== null && $max !== null) {
            $months = (int) CarbonImmutable::parse($min)->diffInMonths(CarbonImmutable::parse($max)) + 1;
        }

        return new SummaryData(
            total: $total,
            notaCount: $ids->count(),
            itemCount: $itemCount,
            supplierCount: $supplierCount,
            avgPerMonth: $months > 0 ? bcdiv($total, (string) $months, 2) : Money::zero(),
        );
    }
}
