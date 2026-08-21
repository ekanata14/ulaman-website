<?php

namespace App\Actions\Purchase;

use App\DTOs\Purchase\CalculatedPurchaseData;
use App\DTOs\Purchase\PurchaseData;
use App\Models\Purchase;
use App\Models\PurchaseItem;

/**
 * §10.2 — Diff & persist baris item (create/update/delete by id), memelihara
 * urutan, menulis nilai terhitung dari CalculatePurchaseTotals.
 */
class SyncPurchaseItems
{
    /**
     * @return array<string, PurchaseItem> peta uid => model, untuk sinkron bundle
     */
    public function execute(Purchase $purchase, PurchaseData $data, CalculatedPurchaseData $calc): array
    {
        $keep = [];
        $map = [];

        foreach ($data->items as $i => $item) {
            $key = $item->uid ?? ('idx-'.$i);
            $c = $calc->items[$key];

            $attrs = [
                'item_id' => $item->itemId,
                'deskripsi' => $item->deskripsi,
                'qty' => $item->qty,
                'unit_id' => $item->unitId,
                'harga_satuan' => $item->hargaSatuan,
                'diskon_tipe' => $item->diskonTipe,
                'diskon_nilai' => $item->diskonNilai,
                'diskon_amount' => $c->diskonAmount,
                'subtotal' => $c->subtotal,
                'alokasi_diskon_bundle' => $c->alokasiDiskonBundle,
                'alokasi_diskon_nota' => $c->alokasiDiskonNota,
                'total' => $c->total,
                'net_total' => $c->netTotal,
                'remark' => $item->remark,
                'urutan' => $i,
            ];

            if ($item->id !== null) {
                $model = $purchase->items()->whereKey($item->id)->firstOrFail();
                $model->update($attrs);
            } else {
                $model = $purchase->items()->create($attrs);
            }

            $keep[] = $model->getKey();
            $map[$key] = $model;
        }

        $purchase->items()->whereNotIn('id', $keep === [] ? [0] : $keep)->delete();

        return $map;
    }
}
