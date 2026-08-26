<?php

use App\Actions\Calculation\AllocateDiscountProportionally;
use App\Actions\Calculation\CalculateBundleDiscount;
use App\Actions\Calculation\CalculateItemTotal;
use App\Actions\Calculation\CalculatePurchaseTotals;
use App\Actions\Calculation\SplitPackagePriceByQty;
use App\DTOs\Purchase\PurchaseBundleData;
use App\DTOs\Purchase\PurchaseData;
use App\DTOs\Purchase\PurchaseItemData;
use App\Enums\BundleType;
use App\Enums\DiscountType;
use App\Enums\PurchaseStatus;
use App\Exceptions\CalculationException;

function mkItem(string $uid, string $qty, ?string $harga, DiscountType $tipe = DiscountType::NONE, string $nilai = '0', int $urutan = 0): PurchaseItemData
{
    return new PurchaseItemData(
        uid: $uid,
        id: null,
        itemId: null,
        deskripsi: 'Item '.$uid,
        qty: $qty,
        unitId: null,
        hargaSatuan: $harga,
        diskonTipe: $tipe,
        diskonNilai: $nilai,
        remark: null,
        urutan: $urutan,
    );
}

/**
 * @param  array<int, PurchaseItemData>  $items
 * @param  array<int, PurchaseBundleData>  $bundles
 */
function mkPurchase(array $items, array $bundles = [], ?DiscountType $notaTipe = null, string $notaNilai = '0'): PurchaseData
{
    return new PurchaseData(
        id: null,
        tanggal: '2026-08-06',
        supplier: null,
        nomorNota: null,
        remark: null,
        status: PurchaseStatus::FINAL,
        diskonNotaTipe: $notaTipe,
        diskonNotaNilai: $notaNilai,
        items: $items,
        bundles: $bundles,
    );
}

function totals(): CalculatePurchaseTotals
{
    return new CalculatePurchaseTotals(
        new CalculateItemTotal,
        new CalculateBundleDiscount,
        new AllocateDiscountProportionally,
    );
}

// ---------------------------------------------------------------------------
// §22.A — Kasus Uji Perhitungan (wajib)
// ---------------------------------------------------------------------------

it('Kasus 1 — diskon item persen (Gresik 20 × 61.000, 5%)', function () {
    $r = (new CalculateItemTotal)->execute(mkItem('a', '20', '61000', DiscountType::PERSEN, '5'));

    expect($r->subtotal)->toBe('1220000.00')
        ->and($r->diskonAmount)->toBe('61000.00')
        ->and($r->total)->toBe('1159000.00');
});

it('Kasus 2 — bundle harga paket UD. Harta Ayu (26jt atas 30jt)', function () {
    $items = [
        mkItem('p35', '1', '12000000'),
        mkItem('t35', '1', '10000000'),
        mkItem('p30', '1', '8000000'),
    ];
    $bundle = new PurchaseBundleData('Paket Marmer', BundleType::HARGA_PAKET, '26000000', ['p35', 't35', 'p30']);

    $r = totals()->execute(mkPurchase($items, [$bundle]));

    expect($r->totalDiskonBundle)->toBe('4000000.00')
        ->and($r->grandTotal)->toBe('26000000.00')
        ->and($r->items['p35']->alokasiDiskonBundle)->toBe('1600000.00')
        ->and($r->items['t35']->alokasiDiskonBundle)->toBe('1333333.00')
        ->and($r->items['p30']->alokasiDiskonBundle)->toBe('1066667.00');

    $sumAlok = bcadd(bcadd($r->items['p35']->alokasiDiskonBundle, $r->items['t35']->alokasiDiskonBundle, 2), $r->items['p30']->alokasiDiskonBundle, 2);
    expect($sumAlok)->toBe('4000000.00');
});

it('Kasus 3 — diskon item + bundle', function () {
    $items = [
        mkItem('besi10', '9', '75000', DiscountType::PERSEN, '5'),
        mkItem('besi6', '10', '29000'),
        mkItem('bendrat', '2', '20000'),
    ];
    $bundle = new PurchaseBundleData('Besi', BundleType::PERSEN, '10', ['besi10', 'besi6', 'bendrat']);

    $r = totals()->execute(mkPurchase($items, [$bundle]));

    expect($r->subtotal)->toBe('1005000.00')
        ->and($r->totalDiskonItem)->toBe('33750.00')
        ->and($r->totalDiskonBundle)->toBe('97125.00')
        ->and($r->grandTotal)->toBe('874125.00');
});

it('Kasus 4 — qty desimal (Pertamina Dex 41,56 × 21.150)', function () {
    $r = (new CalculateItemTotal)->execute(mkItem('dex', '41.56', '21150'));

    expect($r->subtotal)->toBe('878994.00')
        ->and($r->total)->toBe('878994.00');
});

it('Kasus 5 — diskon nominal melebihi subtotal ditolak', function () {
    (new CalculateItemTotal)->execute(mkItem('x', '1', '1500000', DiscountType::NOMINAL, '2000000'));
})->throws(CalculationException::class);

it('Kasus 6 — bundle basis 0 → alokasi nol tanpa exception', function () {
    $alok = (new AllocateDiscountProportionally)->execute(['a' => '0.00', 'b' => '0.00'], '0');

    expect($alok)->toBe(['a' => '0.00', 'b' => '0.00']);
});

it('Kasus 7 — diskon Rp 100 pada 3 item setara → 34/33/33', function () {
    $alok = (new AllocateDiscountProportionally)->execute(['a' => '100', 'b' => '100', 'c' => '100'], '100');

    expect($alok)->toBe(['a' => '34.00', 'b' => '33.00', 'c' => '33.00']);
    expect(bcadd(bcadd($alok['a'], $alok['b'], 2), $alok['c'], 2))->toBe('100.00');
});

// ---------------------------------------------------------------------------
// Invariant §7 Langkah 4 & properti alokasi
// ---------------------------------------------------------------------------

it('menjaga invariant Σ net_item == grand_total (item + bundle + diskon nota)', function () {
    $items = [
        mkItem('besi10', '9', '75000', DiscountType::PERSEN, '5'),
        mkItem('besi6', '10', '29000'),
        mkItem('bendrat', '2', '20000'),
    ];
    $bundle = new PurchaseBundleData('Besi', BundleType::PERSEN, '10', ['besi10', 'besi6', 'bendrat']);

    $r = totals()->execute(mkPurchase($items, [$bundle], DiscountType::PERSEN, '7'));

    $sumNet = '0.00';
    foreach ($r->items as $it) {
        $sumNet = bcadd($sumNet, $it->netTotal, 2);
    }
    expect($sumNet)->toBe($r->grandTotal);
});

it('alokasi selalu berjumlah persis sama dengan diskon (beberapa kombinasi)', function (array $bobot, string $diskon) {
    $alok = (new AllocateDiscountProportionally)->execute($bobot, $diskon);

    $sum = '0.00';
    foreach ($alok as $a) {
        $sum = bcadd($sum, $a, 2);
    }
    expect($sum)->toBe(bcadd($diskon, '0', 2));
})->with([
    [['a' => '333333', 'b' => '333333', 'c' => '333334'], '100'],
    [['a' => '1', 'b' => '2', 'c' => '3', 'd' => '4'], '9999'],
    [['a' => '10000', 'b' => '3', 'c' => '3'], '7'],
    [['a' => '999999', 'b' => '1'], '13310'],
]);

it('memecah harga paket proporsional terhadap qty (harga satuan estimasi)', function () {
    $harga = (new SplitPackagePriceByQty(new AllocateDiscountProportionally))
        ->execute(['a' => '2', 'b' => '2', 'c' => '1'], '25000');

    // Total alokasi harus persis 25.000 (2·harga_a + 2·harga_b + 1·harga_c).
    $totalA = bcmul('2', $harga['a'], 2);
    $totalB = bcmul('2', $harga['b'], 2);
    $totalC = bcmul('1', $harga['c'], 2);
    expect(bcadd(bcadd($totalA, $totalB, 2), $totalC, 2))->toBe('25000.00');
});

it('menolak persen di luar 0–100', function () {
    (new CalculateItemTotal)->execute(mkItem('x', '1', '1000', DiscountType::PERSEN, '150'));
})->throws(CalculationException::class);
