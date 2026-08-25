<?php

namespace App\Actions\Purchase;

use App\DTOs\Purchase\PurchaseBundleData;
use App\DTOs\Purchase\PurchaseData;
use App\DTOs\Purchase\PurchaseItemData;
use App\DTOs\Supplier\SupplierData;
use App\Models\Purchase;

/**
 * Merakit PurchaseData LENGKAP dari model Purchase — mempertahankan diskon item,
 * diskon nota, dan komposisi bundle. Dipakai editor Spreadsheet agar setiap edit
 * inline (yang menyimpan ulang seluruh nota via UpdatePurchase) tidak menghilangkan
 * data apa pun. Berbeda dengan PurchaseForm::toDto() yang membuang diskon nota.
 */
class BuildPurchaseData
{
    public function execute(Purchase $purchase): PurchaseData
    {
        $purchase->loadMissing(['items', 'bundles.bundleItems']);

        $items = [];
        foreach ($purchase->items->sortBy('urutan')->values() as $i => $it) {
            $items[] = new PurchaseItemData(
                uid: (string) $it->id,
                id: $it->id,
                itemId: $it->item_id,
                deskripsi: (string) $it->deskripsi,
                qty: (string) $it->qty,
                unitId: $it->unit_id,
                hargaSatuan: $it->harga_satuan !== null ? (string) $it->harga_satuan : null,
                diskonTipe: $it->diskon_tipe,
                diskonNilai: (string) $it->diskon_nilai,
                remark: $it->remark,
                urutan: $i,
            );
        }

        $bundles = [];
        foreach ($purchase->bundles as $b) {
            $bundles[] = new PurchaseBundleData(
                nama: (string) $b->nama,
                tipe: $b->tipe_diskon,
                nilai: (string) $b->nilai_diskon,
                itemUids: $b->bundleItems->map(fn ($bi): string => (string) $bi->purchase_item_id)->all(),
            );
        }

        return new PurchaseData(
            id: $purchase->id,
            tanggal: $purchase->tanggal->format('Y-m-d'),
            supplier: $purchase->supplier_id !== null
                ? new SupplierData(id: $purchase->supplier_id, nama: '')
                : null,
            nomorNota: $purchase->nomor_nota,
            categoryId: $purchase->category_id,
            metodeBayar: $purchase->metode_bayar,
            remark: $purchase->remark,
            status: $purchase->status,
            diskonNotaTipe: $purchase->diskon_nota_tipe,
            diskonNotaNilai: (string) $purchase->diskon_nota_nilai,
            items: $items,
            bundles: $bundles,
        );
    }
}
