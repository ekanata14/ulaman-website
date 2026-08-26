<?php

use App\Actions\Purchase\BuildPurchaseData;
use App\Actions\Purchase\StorePurchase;
use App\Actions\Purchase\UpdatePurchase;
use App\DTOs\Purchase\PurchaseBundleData;
use App\DTOs\Purchase\PurchaseData;
use App\DTOs\Purchase\PurchaseItemData;
use App\Enums\BundleType;
use App\Enums\DiscountType;
use App\Enums\PurchaseStatus;
use App\Livewire\Admin\Purchase\Spreadsheet;
use App\Livewire\Guest\PurchaseBrowser;
use App\Models\Purchase;
use App\Models\User;
use Livewire\Livewire;

function ssActor(): User
{
    return User::factory()->create(['role' => 'admin', 'is_active' => true]);
}

function ssItem(string $uid, string $desc, string $qty, string $harga, DiscountType $tipe = DiscountType::NONE, string $nilai = '0'): PurchaseItemData
{
    return new PurchaseItemData(
        uid: $uid, id: null, itemId: null, deskripsi: $desc, qty: $qty, unitId: null,
        hargaSatuan: $harga, diskonTipe: $tipe, diskonNilai: $nilai, remark: null, urutan: 0,
    );
}

/**
 * @param  array<int, PurchaseItemData>  $items
 * @param  array<int, PurchaseBundleData>  $bundles
 */
function ssMake(User $a, array $items, array $bundles = [], string $tanggal = '2026-08-06', ?DiscountType $notaTipe = null, string $notaNilai = '0'): Purchase
{
    return app(StorePurchase::class)->execute(new PurchaseData(
        id: null, tanggal: $tanggal, supplier: null, nomorNota: null,
        remark: null, status: PurchaseStatus::FINAL,
        diskonNotaTipe: $notaTipe, diskonNotaNilai: $notaNilai,
        items: $items, bundles: $bundles,
    ), $a);
}

it('BuildPurchaseData mempertahankan diskon item, diskon nota, dan bundle (round-trip)', function () {
    $a = ssActor();
    $purchase = ssMake(
        $a,
        [ssItem('a', 'A', '2', '10000', DiscountType::PERSEN, '5'), ssItem('b', 'B', '3', '10000')],
        [new PurchaseBundleData('Bnd', BundleType::PERSEN, '10', ['a', 'b'])],
        notaTipe: DiscountType::PERSEN,
        notaNilai: '10',
    );
    $before = $purchase->fresh();

    // Round-trip: bangun DTO lengkap lalu simpan ulang tanpa perubahan.
    $dto = app(BuildPurchaseData::class)->execute($purchase);
    app(UpdatePurchase::class)->execute($purchase, $dto, $a);
    $after = $purchase->fresh();

    expect($after->grand_total)->toBe($before->grand_total)
        ->and($after->total_diskon_item)->toBe($before->total_diskon_item)
        ->and($after->total_diskon_bundle)->toBe($before->total_diskon_bundle)
        ->and($after->diskon_nota_amount)->toBe($before->diskon_nota_amount)
        ->and($after->diskon_nota_tipe)->toBe(DiscountType::PERSEN)
        ->and($after->bundles()->count())->toBe(1);
});

it('spreadsheet: edit qty menghitung ulang grand_total', function () {
    $a = ssActor();
    $p = ssMake($a, [ssItem('a', 'Besi', '2', '10000'), ssItem('b', 'Paku', '3', '5000')]); // 35.000
    $item = $p->items()->orderBy('urutan')->first();

    Livewire::actingAs($a)->test(Spreadsheet::class)
        ->call('updateItemField', $p->id, $item->id, 'qty', '5');

    expect($p->fresh()->grand_total)->toBe('65000.00'); // 5*10000 + 3*5000
});

it('spreadsheet: tambah baris item', function () {
    $a = ssActor();
    $p = ssMake($a, [ssItem('a', 'Besi', '2', '10000')]);

    Livewire::actingAs($a)->test(Spreadsheet::class)->call('addItemRow', $p->id);

    expect($p->fresh()->items()->count())->toBe(2);
});

it('spreadsheet: hapus item bukan terakhir → item hilang & total turun', function () {
    $a = ssActor();
    $p = ssMake($a, [ssItem('a', 'Besi', '2', '10000'), ssItem('b', 'Paku', '3', '5000')]);
    $item = $p->items()->orderBy('urutan')->first();

    Livewire::actingAs($a)->test(Spreadsheet::class)->call('deleteItem', $p->id, $item->id);

    expect($p->fresh()->items()->count())->toBe(1)
        ->and($p->fresh()->grand_total)->toBe('15000.00');
});

it('spreadsheet: hapus item terakhir menghapus seluruh nota', function () {
    $a = ssActor();
    $p = ssMake($a, [ssItem('a', 'Besi', '2', '10000')]);
    $item = $p->items()->first();

    Livewire::actingAs($a)->test(Spreadsheet::class)->call('deleteItem', $p->id, $item->id);

    expect(Purchase::find($p->id))->toBeNull();
});

it('spreadsheet: openAddForm membuka modal form lengkap (mode tambah)', function () {
    Livewire::actingAs(ssActor())->test(Spreadsheet::class)
        ->call('openAddForm')
        ->assertSet('formModalOpen', true)
        ->assertSet('editingPurchaseId', null);
});

it('spreadsheet: openEditForm membuka modal form lengkap untuk nota terpilih', function () {
    $a = ssActor();
    $p = ssMake($a, [ssItem('a', 'Semen', '1', '1000')], tanggal: '2026-08-06');

    Livewire::actingAs($a)->test(Spreadsheet::class)
        ->call('openEditForm', $p->id)
        ->assertSet('formModalOpen', true)
        ->assertSet('editingPurchaseId', $p->id);
});

it('spreadsheet: event purchase-form-saved menutup modal', function () {
    Livewire::actingAs(ssActor())->test(Spreadsheet::class)
        ->call('openAddForm')
        ->assertSet('formModalOpen', true)
        ->call('closeForm')
        ->assertSet('formModalOpen', false);
});

it('spreadsheet: loadMore menaikkan limit', function () {
    Livewire::actingAs(ssActor())->test(Spreadsheet::class)
        ->assertSet('limit', 20)
        ->call('loadMore')
        ->assertSet('limit', 40);
});

it('spreadsheet: filter bulan menyaring nota', function () {
    $a = ssActor();
    ssMake($a, [ssItem('a', 'Agustus', '1', '1000')], tanggal: '2026-08-06');
    ssMake($a, [ssItem('a', 'Juli', '1', '1000')], tanggal: '2026-07-06');

    Livewire::actingAs($a)->test(Spreadsheet::class)
        ->set('periodMode', 'month')->set('year', 2026)->set('month', 8)
        ->assertViewHas('totalNotas', 1);
});

it('spreadsheet: mode fullscreen memakai layout focus (nav via dropdown)', function () {
    $a = ssActor();

    $this->actingAs($a)->get(route('admin.spreadsheet', ['focus' => 1]))
        ->assertOk()
        ->assertSee(__('Exit Fullscreen'))
        ->assertSee(__('Menu'));

    $this->actingAs($a)->get(route('admin.spreadsheet'))
        ->assertOk()
        ->assertSee(__('Fullscreen'));
});

it('guest: mode spreadsheet render read-only tanpa method mutasi', function () {
    $a = ssActor();
    ssMake($a, [ssItem('a', 'BesiPublik', '2', '10000')]);

    Livewire::test(PurchaseBrowser::class)
        ->set('viewMode', 'spreadsheet')
        ->assertOk()
        ->assertSee('BesiPublik');

    expect(method_exists(PurchaseBrowser::class, 'updateItemField'))->toBeFalse()
        ->and(method_exists(PurchaseBrowser::class, 'deleteItem'))->toBeFalse()
        ->and(method_exists(PurchaseBrowser::class, 'saveNota'))->toBeFalse();
});
