<?php

namespace App\Actions\Report;

use App\Actions\Report\Concerns\AppliesPurchaseFilters;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Builder;

/**
 * §10.6 — Query Action daftar nota (Mode Nota) untuk guest. Hanya status final,
 * eager load supplier + jumlah item (anti N+1).
 */
class GetPurchaseList
{
    use AppliesPurchaseFilters;

    /**
     * @return Builder<Purchase>
     */
    public function execute(PurchaseFilterData $f): Builder
    {
        $query = Purchase::query()
            ->final()
            ->with('supplier')
            ->withCount('items');

        $this->applyPurchaseFilters($query, $f);

        return $query
            ->orderBy($this->safeSort($f->sort), $this->safeOrder($f->order))
            ->orderBy('id', $this->safeOrder($f->order));
    }
}
