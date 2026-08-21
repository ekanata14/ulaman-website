<?php

use App\Actions\Purchase\StorePurchase;
use App\DTOs\Purchase\PurchaseBundleData;
use App\DTOs\Purchase\PurchaseData;
use App\DTOs\Purchase\PurchaseItemData;
use App\DTOs\Supplier\SupplierData;
use App\Enums\BundleType;
use App\Enums\DiscountType;
use App\Enums\PurchaseStatus;
use App\Livewire\Admin\Purchase\Form;
use App\Livewire\Admin\Purchase\PhotoUploader;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

function bpUser(): User
{
    return User::factory()->create(['role' => 'admin', 'is_active' => true]);
}

function simpleItem(string $uid, string $harga): PurchaseItemData
{
    return new PurchaseItemData(
        uid: $uid, id: null, itemId: null, deskripsi: 'Item '.$uid, qty: '1',
        unitId: null, hargaSatuan: $harga, diskonTipe: DiscountType::NONE, diskonNilai: '0', remark: null, urutan: 0,
    );
}

it('menyembunyikan BundleManager sampai 2 item dicentang', function () {
    $component = Livewire::actingAs(bpUser())->test(Form::class)->call('addItem');
    $items = $component->get('form.items');

    $component->set('selectedForBundle', [$items[0]['uid']])->assertDontSee(__('Create Bundle'));
    $component->set('selectedForBundle', [$items[0]['uid'], $items[1]['uid']])->assertSee(__('Create Bundle'));
});

it('membuat bundle & menolak item yang sudah menjadi anggota bundle lain', function () {
    $component = Livewire::actingAs(bpUser())->test(Form::class)->call('addItem')->call('addItem');
    $items = $component->get('form.items');
    [$u0, $u1, $u2] = [$items[0]['uid'], $items[1]['uid'], $items[2]['uid']];

    foreach ([0, 1, 2] as $i) {
        $component->set("form.items.$i.deskripsi", "Item $i")
            ->set("form.items.$i.qty", '1')->set("form.items.$i.hargaSatuan", '1000');
    }

    $component->set('selectedForBundle', [$u0, $u1])
        ->set('bundleNama', 'Bundle 1')->set('bundleTipe', 'PERSEN')->set('bundleNilai', '10')
        ->call('createBundle');
    expect($component->get('form.bundles'))->toHaveCount(1);

    // u1 sudah dalam bundle → ditolak
    $component->set('selectedForBundle', [$u1, $u2])
        ->set('bundleNama', 'Bundle 2')->set('bundleNilai', '5')
        ->call('createBundle');
    expect($component->get('form.bundles'))->toHaveCount(1);
});

it('membubarkan bundle otomatis saat anggota tersisa < 2', function () {
    $component = Livewire::actingAs(bpUser())->test(Form::class)->call('addItem');
    $items = $component->get('form.items');
    [$u0, $u1] = [$items[0]['uid'], $items[1]['uid']];

    foreach ([0, 1] as $i) {
        $component->set("form.items.$i.deskripsi", "Item $i")
            ->set("form.items.$i.qty", '1')->set("form.items.$i.hargaSatuan", '1000');
    }

    $component->set('selectedForBundle', [$u0, $u1])
        ->set('bundleNama', 'B')->set('bundleNilai', '10')
        ->call('createBundle');
    expect($component->get('form.bundles'))->toHaveCount(1);

    $component->call('removeItem', $u0);
    expect($component->get('form.bundles'))->toHaveCount(0);
});

it('kasus nyata UD. Harta Ayu — HARGA_PAKET 26jt → grand total 26.000.000', function () {
    $supplier = Supplier::create(['nama' => 'UD. Harta Ayu']);

    $data = new PurchaseData(
        id: null,
        tanggal: '2026-08-06',
        supplier: new SupplierData(id: $supplier->id, nama: ''),
        nomorNota: 'HARTA-AYU-01',
        categoryId: null,
        metodeBayar: null,
        remark: null,
        status: PurchaseStatus::FINAL,
        diskonNotaTipe: null,
        diskonNotaNilai: '0',
        items: [
            simpleItem('padma35', '12000000'),
            simpleItem('tugu35', '10000000'),
            simpleItem('padma30', '8000000'),
        ],
        bundles: [
            new PurchaseBundleData(
                nama: 'Paket Marmer',
                tipe: BundleType::HARGA_PAKET,
                nilai: '26000000',
                itemUids: ['padma35', 'tugu35', 'padma30'],
            ),
        ],
    );

    $purchase = app(StorePurchase::class)->execute($data, bpUser());

    expect($purchase->grand_total)->toBe('26000000.00')
        ->and($purchase->total_diskon_bundle)->toBe('4000000.00')
        ->and($purchase->items()->orderBy('urutan')->pluck('alokasi_diskon_bundle')->all())
        ->toBe(['1600000.00', '1333333.00', '1066667.00']);
});

it('PhotoUploader merender untuk nota tersimpan', function () {
    $purchase = app(StorePurchase::class)->execute(
        new PurchaseData(
            id: null, tanggal: '2026-08-06', supplier: null, nomorNota: null, categoryId: null,
            metodeBayar: null, remark: null, status: PurchaseStatus::FINAL, diskonNotaTipe: null,
            diskonNotaNilai: '0', items: [simpleItem('a', '1000')], bundles: [],
        ),
        bpUser(),
    );

    Livewire::actingAs(bpUser())
        ->test(PhotoUploader::class, ['purchase' => $purchase])
        ->assertOk();
});
