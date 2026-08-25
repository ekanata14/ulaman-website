<?php

use App\Enums\PurchaseStatus;
use App\Livewire\Guest\GlobalSearch;
use App\Models\Category;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Supplier;
use Livewire\Livewire;

beforeEach(function () {
    $this->kategori = Category::create(['nama' => 'Semen Bangunan']);
    $this->item = Item::create(['nama' => 'Semen Gresik', 'category_id' => $this->kategori->id]);
    $this->supplier = Supplier::create(['nama' => 'CV Semen Jaya', 'is_active' => true]);
    $this->nota = Purchase::create([
        'kode' => 'NT-FINAL-1', 'tanggal' => '2026-08-10', 'status' => PurchaseStatus::FINAL->value,
        'supplier_id' => $this->supplier->id, 'remark' => 'butuh semen', 'grand_total' => '100000',
    ]);
});

it('menampilkan hasil bergrup saat mengetik kueri', function () {
    Livewire::test(GlobalSearch::class)
        ->set('q', 'semen')
        ->assertSee('CV Semen Jaya')
        ->assertSee('Semen Gresik')
        ->assertSee('NT-FINAL-1');
});

it('tidak mencari untuk kueri di bawah 2 karakter', function () {
    Livewire::test(GlobalSearch::class)
        ->set('q', 's')
        ->assertDontSee('CV Semen Jaya');
});

it('memilih supplier men-dispatch guest-filter-supplier lalu menutup overlay', function () {
    Livewire::test(GlobalSearch::class)
        ->set('open', true)
        ->call('pickSupplier', $this->supplier->id)
        ->assertDispatched('guest-filter-supplier', id: $this->supplier->id)
        ->assertSet('open', false);
});

it('memilih nota membuka modal detail lewat event yang ada', function () {
    Livewire::test(GlobalSearch::class)
        ->call('pickNote', $this->nota->id)
        ->assertDispatched('open-purchase-detail', purchaseId: $this->nota->id);
});

it('memilih item men-dispatch guest-search-items', function () {
    Livewire::test(GlobalSearch::class)
        ->call('pickItem', 'Semen Gresik')
        ->assertDispatched('guest-search-items', term: 'Semen Gresik');
});
