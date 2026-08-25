<?php

namespace App\Actions\Report;

use App\Actions\Report\Concerns\AppliesPurchaseFilters;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query Action untuk mode Spreadsheet: nota ter-filter (paginasi PER-NOTA agar
 * grup item tak terpotong), eager-load item/supplier/bundle (anti N+1), urut
 * kronologis. onlyFinal=true untuk guest (hanya nota final); admin melihat semua.
 */
class GetPurchaseNotasForSpreadsheet
{
    use AppliesPurchaseFilters;

    /**
     * @return Builder<Purchase>
     */
    public function execute(PurchaseFilterData $f, bool $onlyFinal = false): Builder
    {
        $query = Purchase::query()
            ->with([
                'supplier',
                'items' => fn ($q) => $q->orderBy('urutan')->orderBy('id'),
                'bundles.bundleItems',
            ])
            ->when($onlyFinal, fn (Builder $q): Builder => $q->final());

        $this->applyPurchaseFilters($query, $f);

        return $query->orderBy('tanggal')->orderBy('id');
    }
}
