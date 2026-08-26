<?php

use App\Livewire\Admin\Purchase\Form;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function pfdAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'is_active' => true]);
}

it('menerapkan diskon nota & bukti transfer saat simpan (status FINAL, tanpa kategori/metode)', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    Livewire::actingAs(pfdAdmin())->test(Form::class)
        ->set('form.items.0.deskripsi', 'Semen')
        ->set('form.items.0.qty', '10')
        ->set('form.items.0.hargaSatuan', '10000')
        ->set('form.diskonNotaTipe', 'PERSEN')
        ->set('form.diskonNotaNilai', '10')
        ->set('buktiTransfers', [UploadedFile::fake()->image('bt.jpg')])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.purchases'));

    $purchase = Purchase::latest('id')->first();

    expect($purchase)->not->toBeNull()
        ->and($purchase->status->value)->toBe('final')
        // subtotal 100.000 − diskon nota 10% (10.000) = 90.000
        ->and($purchase->grand_total)->toBe('90000.00')
        ->and($purchase->diskon_nota_amount)->toBe('10000.00')
        ->and($purchase->buktiTransfers()->count())->toBe(1)
        ->and($purchase->photos()->count())->toBe(0);
});

it('mode embedded: simpan men-dispatch purchase-form-saved tanpa redirect', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    Livewire::actingAs(pfdAdmin())->test(Form::class, ['embedded' => true])
        ->set('form.items.0.deskripsi', 'Semen')
        ->set('form.items.0.qty', '2')
        ->set('form.items.0.hargaSatuan', '5000')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('purchase-form-saved')
        ->assertNoRedirect();

    expect(Purchase::whereHas('items', fn ($q) => $q->where('deskripsi', 'Semen'))->exists())->toBeTrue();
});

it('pratinjau memakai diskon nota terkini setelah Apply Discount', function () {
    $component = Livewire::actingAs(pfdAdmin())->test(Form::class)
        ->set('form.items.0.deskripsi', 'Batu')
        ->set('form.items.0.qty', '2')
        ->set('form.items.0.hargaSatuan', '50000')
        ->set('form.diskonNotaTipe', 'NOMINAL')
        ->set('form.diskonNotaNilai', '25000')
        ->call('applyDiscount')
        ->assertHasNoErrors();

    // 100.000 − 25.000 = 75.000
    expect($component->viewData('preview')->grandTotal)->toBe('75000.00')
        ->and($component->viewData('preview')->diskonNotaAmount)->toBe('25000.00');
});

it('form nota tetap merender dengan bukti transfer sementara di properti (regresi shadow)', function () {
    Livewire::actingAs(pfdAdmin())->test(Form::class)
        ->set('buktiTransfers', [UploadedFile::fake()->image('bt.jpg')])
        ->assertOk();
});
