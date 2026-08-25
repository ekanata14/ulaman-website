<?php

use App\Actions\Purchase\StorePurchase;
use App\Actions\Report\GetPurchaseSummary;
use App\DTOs\Purchase\PurchaseData;
use App\DTOs\Purchase\PurchaseFilterData;
use App\DTOs\Purchase\PurchaseItemData;
use App\DTOs\Supplier\SupplierData;
use App\Enums\DiscountType;
use App\Enums\PurchaseStatus;
use App\Livewire\Guest\PurchaseBrowser;
use App\Livewire\Guest\PurchaseDetailModal;
use App\Livewire\Guest\SummaryCards;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

function nota(string $supplierNama, string $harga, string $tanggal, PurchaseStatus $status = PurchaseStatus::FINAL): Purchase
{
    $supplier = Supplier::firstOrCreate(['nama' => $supplierNama]);

    return app(StorePurchase::class)->execute(
        new PurchaseData(
            id: null, tanggal: $tanggal,
            supplier: new SupplierData(id: $supplier->id, nama: ''),
            nomorNota: null, categoryId: null, metodeBayar: null, remark: null,
            status: $status, diskonNotaTipe: null, diskonNotaNilai: '0',
            items: [new PurchaseItemData(
                uid: 'a', id: null, itemId: null, deskripsi: 'Barang', qty: '1',
                unitId: null, hargaSatuan: $harga, diskonTipe: DiscountType::NONE, diskonNilai: '0', remark: null, urutan: 0,
            )],
            bundles: [],
        ),
        User::factory()->create(['role' => 'admin']),
    );
}

it('AC-06.1 — URL root menampilkan tabel tanpa login', function () {
    $n = nota('Murda Jaya', '100000', '2026-08-10');

    $this->get('/')->assertOk()->assertSeeLivewire(PurchaseBrowser::class);
    Livewire::test(PurchaseBrowser::class)->assertOk()->assertSee($n->kode);
});

it('AC-06.2 — filter supplier akurat & ringkasan menyesuaikan', function () {
    $murda = nota('Murda Jaya', '100000', '2026-08-10');
    $other = nota('Alam Santara', '50000', '2026-08-10');

    Livewire::test(PurchaseBrowser::class)
        ->set('supplierIds', [$murda->supplier_id])
        ->assertSee($murda->kode)
        ->assertDontSee($other->kode);

    $summary = app(GetPurchaseSummary::class)->execute(new PurchaseFilterData(supplierIds: [$murda->supplier_id]));
    expect($summary->total)->toBe('100000.00')
        ->and($summary->notaCount)->toBe(1);
});

it('AC-06.3 — filter terhidrasi dari query string (#[Url], shareable)', function () {
    Livewire::withQueryParams(['q' => 'Murda', 'dari' => '2026-08-01', 'mode' => 'item'])
        ->test(PurchaseBrowser::class)
        ->assertSet('search', 'Murda')
        ->assertSet('startDate', '2026-08-01')
        ->assertSet('viewMode', 'item');
});

it('AC-06.4 — komponen guest tidak punya method mutasi (tidak ada sama sekali)', function () {
    $mutations = ['save', 'store', 'update', 'delete', 'destroy', 'create', 'confirmDelete'];

    foreach ([PurchaseBrowser::class, PurchaseDetailModal::class, SummaryCards::class] as $component) {
        foreach ($mutations as $method) {
            expect(method_exists($component, $method))->toBeFalse();
        }
    }

    // Memanggil method mutasi yang tidak ada → Livewire menolak (bukan 200).
    expect(fn () => Livewire::test(PurchaseBrowser::class)->call('save'))->toThrow(Exception::class);
});

it('modal filter tertutup secara default dan bisa dibuka (state presentasi)', function () {
    nota('Murda Jaya', '100000', '2026-08-10');

    Livewire::test(PurchaseBrowser::class)
        ->assertSet('showFilters', false)
        ->set('showFilters', true)
        ->assertSet('showFilters', true);
});

it('menghitung jumlah filter aktif untuk badge tombol Filter', function () {
    $n = nota('Murda Jaya', '100000', '2026-08-10');

    $component = Livewire::test(PurchaseBrowser::class)
        ->set('search', 'Murda')
        ->set('supplierIds', [$n->supplier_id]);

    expect($component->instance()->activeFilterCount())->toBe(2);
});

it('event guest-filter-supplier menerapkan filter supplier & mode nota', function () {
    $n = nota('Murda Jaya', '100000', '2026-08-10');

    Livewire::test(PurchaseBrowser::class)
        ->set('viewMode', 'item')
        ->dispatch('guest-filter-supplier', id: $n->supplier_id)
        ->assertSet('supplierIds', [$n->supplier_id])
        ->assertSet('viewMode', 'nota');
});

it('event guest-search-items mengisi pencarian & beralih ke mode item', function () {
    nota('Murda Jaya', '100000', '2026-08-10');

    Livewire::test(PurchaseBrowser::class)
        ->dispatch('guest-search-items', term: 'Semen')
        ->assertSet('search', 'Semen')
        ->assertSet('viewMode', 'item');
});

it('nota draft tidak pernah tampil ke guest', function () {
    $final = nota('Murda Jaya', '100000', '2026-08-10');
    $draft = nota('Draft Co', '1000', '2026-08-10', PurchaseStatus::DRAFT);

    Livewire::test(PurchaseBrowser::class)
        ->assertSee($final->kode)
        ->assertDontSee($draft->kode);
});

it('detail modal hanya membuka nota final & merender', function () {
    $n = nota('Murda Jaya', '100000', '2026-08-10');

    Livewire::test(PurchaseDetailModal::class)
        ->call('openDetail', $n->id)
        ->assertSet('modalOpen', true)
        ->assertSee($n->kode);
});

it('SummaryCards lazy merender ringkasan', function () {
    nota('Murda Jaya', '100000', '2026-08-10');

    Livewire::test(SummaryCards::class, ['filter' => []])->assertOk();
});
