<?php

use App\Actions\Unit\StoreUnit;
use App\DTOs\Unit\UnitData;
use App\Livewire\Admin\Purchase\Form;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

function qaAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'is_active' => true]);
}

it('StoreUnit membuat satuan (nama di-trim)', function () {
    $unit = app(StoreUnit::class)->execute(new UnitData(id: null, nama: '  kardus  ', simbol: 'kd'));

    expect($unit->nama)->toBe('kardus');
    $this->assertDatabaseHas('units', ['nama' => 'kardus', 'simbol' => 'kd']);
});

it('quick-add supplier di form nota membuat & memilih supplier', function () {
    $component = Livewire::actingAs(qaAdmin())->test(Form::class)
        ->set('qsNama', 'PT Baru Jaya')
        ->call('saveSupplier')
        ->assertSet('supplierModalOpen', false);

    $supplier = Supplier::firstWhere('nama', 'PT Baru Jaya');
    expect($supplier)->not->toBeNull();
    $component->assertSet('form.supplierId', $supplier->id);
});

it('quick-add satuan mengisi unitId pada baris target', function () {
    $component = Livewire::actingAs(qaAdmin())->test(Form::class)
        ->call('openUnitModal', 0)
        ->set('quNama', 'box')
        ->call('saveUnit');

    $unit = Unit::firstWhere('nama', 'box');
    expect($unit)->not->toBeNull();
    $component->assertSet('form.items.0.unitId', $unit->id);
});

it('quick-add master item mengisi itemId & auto-isi deskripsi baris target', function () {
    $component = Livewire::actingAs(qaAdmin())->test(Form::class)
        ->call('openItemModal', 0)
        ->set('qiNama', 'Semen Merah')
        ->call('saveItem');

    $item = Item::firstWhere('nama', 'Semen Merah');
    expect($item)->not->toBeNull();
    $component->assertSet('form.items.0.itemId', $item->id)
        ->assertSet('form.items.0.deskripsi', 'Semen Merah');
});

it('konfirmasi generik: askConfirm lalu confirmProceed menjalankan metode target', function () {
    $component = Livewire::actingAs(qaAdmin())->test(Form::class);
    $uid = $component->get('form.items')[0]['uid'];

    $component->call('confirmRemoveItem', $uid)
        ->assertSet('confirmModalOpen', true)
        ->assertSet('confirmMethod', 'removeItem')
        ->call('confirmProceed')
        ->assertSet('confirmModalOpen', false);

    expect($component->get('form.items'))->toHaveCount(0);
});
