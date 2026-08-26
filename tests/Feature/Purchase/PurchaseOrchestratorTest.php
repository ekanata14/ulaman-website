<?php

use App\Actions\Maintenance\VerifyPurchaseTotals;
use App\Actions\Purchase\DeletePurchase;
use App\Actions\Purchase\DuplicatePurchase;
use App\Actions\Purchase\StorePurchase;
use App\Actions\Purchase\UpdatePurchase;
use App\DTOs\Purchase\PurchaseBundleData;
use App\DTOs\Purchase\PurchaseData;
use App\DTOs\Purchase\PurchaseItemData;
use App\DTOs\Supplier\SupplierData;
use App\Enums\BundleType;
use App\Enums\DiscountType;
use App\Enums\PurchaseStatus;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;

function actor(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function itemData(string $uid, string $qty, ?string $harga, DiscountType $tipe = DiscountType::NONE, string $nilai = '0', ?int $itemId = null): PurchaseItemData
{
    return new PurchaseItemData(
        uid: $uid, id: null, itemId: $itemId, deskripsi: 'Item '.$uid, qty: $qty,
        unitId: null, hargaSatuan: $harga, diskonTipe: $tipe, diskonNilai: $nilai, remark: null, urutan: 0,
    );
}

/**
 * @param  array<int, PurchaseItemData>  $items
 * @param  array<int, PurchaseBundleData>  $bundles
 */
function purchaseData(array $items, array $bundles = [], ?int $supplierId = null, string $tanggal = '2026-08-06'): PurchaseData
{
    return new PurchaseData(
        id: null, tanggal: $tanggal,
        supplier: $supplierId !== null ? new SupplierData(id: $supplierId, nama: '') : null,
        nomorNota: 'NOTA-1', remark: null,
        status: PurchaseStatus::FINAL, diskonNotaTipe: null, diskonNotaNilai: '0',
        items: $items, bundles: $bundles,
    );
}

it('menyimpan nota (item + bundle) dan menegakkan invariant di DB', function () {
    $data = purchaseData(
        [
            itemData('besi10', '9', '75000', DiscountType::PERSEN, '5'),
            itemData('besi6', '10', '29000'),
            itemData('bendrat', '2', '20000'),
        ],
        [new PurchaseBundleData('Besi', BundleType::PERSEN, '10', ['besi10', 'besi6', 'bendrat'])],
    );

    $purchase = app(StorePurchase::class)->execute($data, actor());

    expect($purchase->kode)->toBe('PB-202608-0001')
        ->and($purchase->grand_total)->toBe('874125.00')
        ->and($purchase->total_diskon_item)->toBe('33750.00')
        ->and($purchase->total_diskon_bundle)->toBe('97125.00');

    expect($purchase->items()->count())->toBe(3)
        ->and($purchase->bundles()->count())->toBe(1);

    $sumNet = $purchase->items()->get()->reduce(
        fn (string $carry, $it): string => bcadd($carry, (string) $it->net_total, 2),
        '0.00',
    );
    expect($sumNet)->toBe($purchase->grand_total);
});

it('membuat kode nota berurutan dalam bulan yang sama (race-safe)', function () {
    $a = actor();
    $p1 = app(StorePurchase::class)->execute(purchaseData([itemData('a', '1', '1000')]), $a);
    $p2 = app(StorePurchase::class)->execute(purchaseData([itemData('a', '1', '1000')]), $a);

    expect($p1->kode)->toBe('PB-202608-0001')
        ->and($p2->kode)->toBe('PB-202608-0002');
});

it('memperbarui nota dengan diffing item (hapus satu baris) dan hitung ulang', function () {
    $a = actor();
    $data = purchaseData([
        itemData('x', '2', '10000'),
        itemData('y', '3', '5000'),
    ]);
    $purchase = app(StorePurchase::class)->execute($data, $a);
    expect($purchase->grand_total)->toBe('35000.00');

    $updated = app(UpdatePurchase::class)->execute(
        $purchase,
        purchaseData([itemData('x', '2', '10000')]),
        $a,
    );

    expect($updated->fresh()->grand_total)->toBe('20000.00')
        ->and($updated->items()->count())->toBe(1)
        ->and($updated->kode)->toBe('PB-202608-0001'); // kode dipertahankan
});

it('menolak satu item masuk dua bundle (unique index DB)', function () {
    $data = purchaseData(
        [
            itemData('a', '1', '10000'),
            itemData('b', '1', '10000'),
            itemData('c', '1', '10000'),
        ],
        [
            new PurchaseBundleData('B1', BundleType::PERSEN, '10', ['a', 'b']),
            new PurchaseBundleData('B2', BundleType::PERSEN, '10', ['b', 'c']),
        ],
    );

    expect(fn () => app(StorePurchase::class)->execute($data, actor()))
        ->toThrow(QueryException::class);

    // Rollback penuh: tidak ada nota parsial.
    expect(Purchase::count())->toBe(0);
});

it('menghapus nota: soft delete + cascade item & bundle', function () {
    $a = actor();
    $purchase = app(StorePurchase::class)->execute(
        purchaseData(
            [itemData('a', '1', '10000'), itemData('b', '1', '10000')],
            [new PurchaseBundleData('B', BundleType::PERSEN, '10', ['a', 'b'])],
        ),
        $a,
    );
    $id = $purchase->id;

    app(DeletePurchase::class)->execute($purchase, $a);

    expect(Purchase::withTrashed()->find($id)->trashed())->toBeTrue()
        ->and(Purchase::find($id))->toBeNull()
        ->and(DB::table('purchase_items')->where('purchase_id', $id)->count())->toBe(0)
        ->and(DB::table('purchase_bundles')->where('purchase_id', $id)->count())->toBe(0);
});

it('menduplikasi nota tanpa foto, status draft, kode baru', function () {
    $a = actor();
    $original = app(StorePurchase::class)->execute(
        purchaseData([itemData('a', '2', '5000'), itemData('b', '1', '3000')]),
        $a,
    );

    $copy = app(DuplicatePurchase::class)->execute($original, $a);

    expect($copy->id)->not->toBe($original->id)
        ->and($copy->status)->toBe(PurchaseStatus::DRAFT)
        ->and($copy->kode)->toBe('PB-202608-0002')
        ->and($copy->items()->count())->toBe(2)
        ->and($copy->grand_total)->toBe($original->grand_total);
});

it('memicu PurchaseSaved yang memperbarui harga_terakhir item master', function () {
    $unit = Unit::create(['nama' => 'sak']);
    $item = Item::create(['nama' => 'Semen Gresik', 'unit_id' => $unit->id]);
    $supplier = Supplier::create(['nama' => 'Murda Jaya']);

    app(StorePurchase::class)->execute(
        purchaseData([itemData('g', '20', '61000', itemId: $item->id)], supplierId: $supplier->id),
        actor(),
    );

    $item->refresh();
    expect($item->harga_terakhir)->toBe('61000.00')
        ->and($item->supplier_terakhir_id)->toBe($supplier->id);
});

it('verify-totals mendeteksi & memperbaiki drift grand_total', function () {
    $purchase = app(StorePurchase::class)->execute(
        purchaseData([itemData('a', '1', '10000'), itemData('b', '2', '5000')]),
        actor(),
    );
    // Rusak grand_total secara manual.
    DB::table('purchases')->where('id', $purchase->id)->update(['grand_total' => '99999.00']);

    $report = app(VerifyPurchaseTotals::class)->execute(fix: false);
    expect($report['mismatches'])->toHaveCount(1);

    $fixReport = app(VerifyPurchaseTotals::class)->execute(fix: true);
    expect($fixReport['fixed'])->toBe(1)
        ->and($purchase->fresh()->grand_total)->toBe('20000.00');
});
