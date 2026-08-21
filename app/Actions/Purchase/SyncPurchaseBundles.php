<?php

namespace App\Actions\Purchase;

use App\DTOs\Purchase\CalculatedPurchaseData;
use App\Models\Purchase;
use App\Models\PurchaseItem;

/**
 * §10.2 / §F-04 — Persist bundle & anggotanya. Aturan: anggota harus satu nota,
 * bundle < 2 anggota dibubarkan (dissolve). Unique index bundle_items menegakkan
 * "1 item maksimal 1 bundle" di level DB.
 */
class SyncPurchaseBundles
{
    /**
     * @param  array<string, PurchaseItem>  $itemMap  peta uid => model dari SyncPurchaseItems
     */
    public function execute(Purchase $purchase, CalculatedPurchaseData $calc, array $itemMap): void
    {
        // Bangun ulang; FK cascadeOnDelete membersihkan bundle_items lama.
        $purchase->bundles()->delete();

        foreach ($calc->bundles as $bundle) {
            $memberUids = array_values(array_filter(
                $bundle->itemUids,
                static fn (string $uid): bool => isset($itemMap[$uid]),
            ));

            if (count($memberUids) < 2) {
                continue; // dissolve (§F-04.6)
            }

            $model = $purchase->bundles()->create([
                'nama' => $bundle->nama,
                'tipe_diskon' => $bundle->tipe,
                'nilai_diskon' => $bundle->nilai,
                'basis_amount' => $bundle->basis,
                'diskon_amount' => $bundle->diskon,
            ]);

            foreach ($memberUids as $uid) {
                $model->bundleItems()->create([
                    'purchase_item_id' => $itemMap[$uid]->getKey(),
                ]);
            }
        }
    }
}
