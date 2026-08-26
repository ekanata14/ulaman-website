<?php

use App\Actions\Report\GetPurchaseItemList;
use App\Actions\Report\GetPurchaseList;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Enums\PurchaseStatus;
use App\Livewire\Guest\PurchaseBrowser;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Livewire\Livewire;

function poPurchase(string $kode, string $tanggal): Purchase
{
    $p = new Purchase;
    $p->forceFill([
        'kode' => $kode,
        'tanggal' => $tanggal,
        'status' => PurchaseStatus::FINAL->value,
        'grand_total' => 1000,
        'subtotal' => 1000,
        'total_diskon_item' => 0,
        'total_diskon_bundle' => 0,
        'diskon_nota_nilai' => 0,
        'diskon_nota_amount' => 0,
    ])->save();

    $item = new PurchaseItem;
    $item->forceFill([
        'purchase_id' => $p->id,
        'deskripsi' => 'Item '.$kode,
        'qty' => 1,
        'harga_satuan' => 1000,
        'diskon_tipe' => 'NONE',
        'diskon_nilai' => 0,
        'diskon_amount' => 0,
        'subtotal' => 1000,
        'net_total' => 1000,
        'alokasi_diskon_bundle' => 0,
        'alokasi_diskon_nota' => 0,
        'urutan' => 0,
    ])->save();

    return $p;
}

function poSeed(): void
{
    poPurchase('PB-JUN', '2026-06-10');
    poPurchase('PB-JUL', '2026-07-10');
    poPurchase('PB-AUG', '2026-08-10');
}

it('GetPurchaseList mengembalikan nota terlama lebih dulu', function () {
    poSeed();

    $first = app(GetPurchaseList::class)->execute(new PurchaseFilterData)->first();

    expect($first->kode)->toBe('PB-JUN');
});

it('GetPurchaseItemList mengembalikan item nota terlama lebih dulu', function () {
    poSeed();

    $first = app(GetPurchaseItemList::class)->execute(new PurchaseFilterData)->first();

    expect($first->purchase->kode)->toBe('PB-JUN');
});

it('PurchaseBrowser default berurutan menaik (terlama dulu)', function () {
    Livewire::test(PurchaseBrowser::class)->assertSet('order', 'asc');
});
