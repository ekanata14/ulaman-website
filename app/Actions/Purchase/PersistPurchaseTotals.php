<?php

namespace App\Actions\Purchase;

use App\DTOs\Purchase\CalculatedPurchaseData;
use App\Models\Purchase;

/**
 * §10.2 — Tulis nilai terhitung tingkat nota ke tabel purchases.
 * Nilai per-item ditulis oleh SyncPurchaseItems.
 */
class PersistPurchaseTotals
{
    public function execute(Purchase $purchase, CalculatedPurchaseData $calc): void
    {
        $purchase->forceFill([
            'subtotal' => $calc->subtotal,
            'total_diskon_item' => $calc->totalDiskonItem,
            'total_diskon_bundle' => $calc->totalDiskonBundle,
            'diskon_nota_amount' => $calc->diskonNotaAmount,
            'grand_total' => $calc->grandTotal,
        ])->save();
    }
}
