<?php

use App\Actions\Report\SearchCatalog;
use App\Enums\PurchaseStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Supplier;

beforeEach(function () {
    $this->kategori = Category::create(['nama' => 'Semen Bangunan']);
    Item::create(['nama' => 'Semen Gresik', 'category_id' => $this->kategori->id]);

    $this->supplierAktif = Supplier::create(['nama' => 'CV Semen Jaya', 'pic' => 'Budi', 'is_active' => true]);
    Supplier::create(['nama' => 'PT Semen Mati', 'is_active' => false]);

    $this->notaFinal = Purchase::create([
        'kode' => 'NT-FINAL-1', 'tanggal' => '2026-08-10', 'status' => PurchaseStatus::FINAL->value,
        'supplier_id' => $this->supplierAktif->id, 'remark' => 'butuh semen', 'grand_total' => '100000',
    ]);
    Purchase::create([
        'kode' => 'NT-DRAFT-1', 'tanggal' => '2026-08-10', 'status' => PurchaseStatus::DRAFT->value,
        'supplier_id' => $this->supplierAktif->id, 'remark' => 'semen draft', 'grand_total' => '5000',
    ]);
});

it('mencari supplier, kategori, item, dan nota berdasarkan istilah', function () {
    $r = app(SearchCatalog::class)->execute('semen');

    expect($r['suppliers']->pluck('nama'))->toContain('CV Semen Jaya')
        ->and($r['categories']->pluck('nama'))->toContain('Semen Bangunan')
        ->and($r['items']->pluck('nama'))->toContain('Semen Gresik')
        ->and($r['notes']->pluck('kode'))->toContain('NT-FINAL-1');
});

it('mengecualikan supplier nonaktif dari hasil', function () {
    $r = app(SearchCatalog::class)->execute('semen');

    expect($r['suppliers']->pluck('nama'))->not->toContain('PT Semen Mati');
});

it('tidak pernah menampilkan nota draft ke guest', function () {
    $r = app(SearchCatalog::class)->execute('semen');

    expect($r['notes']->pluck('kode'))->toContain('NT-FINAL-1')
        ->and($r['notes']->pluck('kode'))->not->toContain('NT-DRAFT-1');
});

it('mencari supplier lewat PIC juga', function () {
    $r = app(SearchCatalog::class)->execute('Budi');

    expect($r['suppliers']->pluck('nama'))->toContain('CV Semen Jaya');
});
