<?php

namespace App\Actions\Report;

use App\Actions\Report\Concerns\AppliesPurchaseFilters;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Builder;

/**
 * §10.6 — Query Action daftar item (Mode Item, flat) untuk guest. Hanya item
 * dari nota final; eager load nota + supplier.
 */
class GetPurchaseItemList
{
    use AppliesPurchaseFilters;

    /**
     * @return Builder<PurchaseItem>
     */
    public function execute(PurchaseFilterData $f): Builder
    {
        $purchaseIds = Purchase::query()->final();
        $this->applyPurchaseFilters($purchaseIds, $f);

        return PurchaseItem::query()
            ->with(['purchase.supplier'])
            ->whereIn('purchase_id', $purchaseIds->select('id'))
            ->orderByDesc('id');
    }
}
