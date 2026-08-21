<?php

namespace App\Actions\Purchase;

use App\DTOs\Purchase\PurchaseBundleData;
use App\DTOs\Purchase\PurchaseData;
use App\DTOs\Purchase\PurchaseItemData;
use App\DTOs\Supplier\SupplierData;
use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * §10.2 — Duplikasi nota (item + bundle, TANPA foto), status draft, tanggal
 * hari ini, kode baru. Delegasi ke StorePurchase agar perhitungan identik.
 */
class DuplicatePurchase
{
    public function __construct(
        private readonly StorePurchase $storePurchase,
    ) {}

    public function execute(Purchase $purchase, User $actor): Purchase
    {
        $purchase->loadMissing(['items', 'bundles.bundleItems']);

        $items = [];
        foreach ($purchase->items as $row) {
            $items[] = new PurchaseItemData(
                uid: 'dup-'.$row->getKey(),
                id: null,
                itemId: $row->item_id,
                deskripsi: $row->deskripsi,
                qty: (string) $row->qty,
                unitId: $row->unit_id,
                hargaSatuan: $row->harga_satuan !== null ? (string) $row->harga_satuan : null,
                diskonTipe: $row->diskon_tipe,
                diskonNilai: (string) $row->diskon_nilai,
                remark: $row->remark,
                urutan: $row->urutan,
            );
        }

        $bundles = [];
        foreach ($purchase->bundles as $bundle) {
            $uids = $bundle->bundleItems
                ->map(static fn ($bi): string => 'dup-'.$bi->purchase_item_id)
                ->all();

            $bundles[] = new PurchaseBundleData(
                nama: $bundle->nama,
                tipe: $bundle->tipe_diskon,
                nilai: (string) $bundle->nilai_diskon,
                itemUids: $uids,
            );
        }

        $data = new PurchaseData(
            id: null,
            tanggal: CarbonImmutable::now()->format('Y-m-d'),
            supplier: $purchase->supplier_id !== null ? new SupplierData(id: $purchase->supplier_id, nama: '') : null,
            nomorNota: null,
            categoryId: $purchase->category_id,
            metodeBayar: $purchase->metode_bayar,
            remark: $purchase->remark,
            status: PurchaseStatus::DRAFT,
            diskonNotaTipe: $purchase->diskon_nota_tipe,
            diskonNotaNilai: (string) $purchase->diskon_nota_nilai,
            items: $items,
            bundles: $bundles,
        );

        return $this->storePurchase->execute($data, $actor);
    }
}
