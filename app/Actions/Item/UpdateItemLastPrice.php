<?php

namespace App\Actions\Item;

use App\Models\Purchase;

/**
 * §F-02.2 / §10.4 — Perbarui harga_terakhir & supplier_terakhir_id item master
 * dari baris nota. Dipicu listener PurchaseSaved.
 */
class UpdateItemLastPrice
{
    public function execute(Purchase $purchase): void
    {
        $purchase->loadMissing('items.item');

        foreach ($purchase->items as $row) {
            $item = $row->item;

            if ($item === null || $row->harga_satuan === null) {
                continue;
            }

            $item->forceFill([
                'harga_terakhir' => $row->harga_satuan,
                'supplier_terakhir_id' => $purchase->supplier_id,
            ])->save();
        }
    }
}
