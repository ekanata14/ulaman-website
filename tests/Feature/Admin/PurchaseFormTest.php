<?php

use App\Livewire\Admin\Purchase\Form;
use App\Livewire\Admin\Purchase\Index as PurchaseIndex;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin', 'is_active' => true]);
}

it('menolak nota tanpa item', function () {
    Livewire::actingAs(adminUser())
        ->test(Form::class)
        ->set('form.items', [])
        ->call('save')
        ->assertHasErrors('form.items');

    expect(Purchase::count())->toBe(0);
});

it('mengarahkan guest ke login saat membuka route admin', function () {
    $this->get(route('admin.purchases'))->assertRedirect(route('login'));
    $this->get(route('admin.purchases.create'))->assertRedirect(route('login'));
});

it('menghapus baris item tengah menyisakan nilai yang benar pada baris lain', function () {
    $component = Livewire::actingAs(adminUser())
        ->test(Form::class)
        ->call('addItem')
        ->call('addItem') // total 3 baris (1 dari mount + 2)
        ->set('form.items.0.deskripsi', 'A')
        ->set('form.items.1.deskripsi', 'B')
        ->set('form.items.2.deskripsi', 'C');

    $midUid = $component->get('form.items')[1]['uid'];

    $component->call('removeItem', $midUid);

    $after = $component->get('form.items');
    expect($after)->toHaveCount(2)
        ->and($after[0]['deskripsi'])->toBe('A')
        ->and($after[1]['deskripsi'])->toBe('C');
});

it('menyimpan nota lewat form dan mengarahkan ke daftar', function () {
    $supplier = Supplier::create(['nama' => 'Murda Jaya']);

    Livewire::actingAs(adminUser())
        ->test(Form::class)
        ->set('form.tanggal', now()->format('Y-m-d'))
        ->set('form.supplierId', $supplier->id)
        ->set('form.items.0.deskripsi', 'Semen Gresik')
        ->set('form.items.0.qty', '20')
        ->set('form.items.0.hargaSatuan', '61000')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.purchases'));

    expect(Purchase::count())->toBe(1)
        ->and(Purchase::first()->grand_total)->toBe('1220000.00');
});

it('admin dapat membuka daftar & form nota', function () {
    $admin = adminUser();
    Livewire::actingAs($admin)->test(PurchaseIndex::class)->assertOk();
    Livewire::actingAs($admin)->test(Form::class)->assertOk();
});
