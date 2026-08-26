<?php

use App\Livewire\Admin\Item\Show as ItemShow;
use App\Livewire\Admin\Supplier\Show as SupplierShow;
use App\Livewire\Admin\Unit\Show as UnitShow;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

function detailManager(): User
{
    return User::factory()->create(['role' => 'admin', 'is_active' => true]);
}

/**
 * Bikin satu nota untuk supplier dengan sejumlah baris barang.
 *
 * @param  array<int, array{deskripsi: string, item_id?: int|null, unit_id?: int|null, qty: string, net_total: string}>  $lines
 */
function seedNota(Supplier $supplier, string $kode, string $grandTotal, array $lines): Purchase
{
    $purchase = Purchase::create([
        'kode' => $kode,
        'tanggal' => now()->toDateString(),
        'supplier_id' => $supplier->id,
        'status' => 'final',
        'grand_total' => $grandTotal,
    ]);

    foreach ($lines as $i => $line) {
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'item_id' => $line['item_id'] ?? null,
            'deskripsi' => $line['deskripsi'],
            'qty' => $line['qty'],
            'unit_id' => $line['unit_id'] ?? null,
            'net_total' => $line['net_total'],
            'urutan' => $i,
        ]);
    }

    return $purchase;
}

it('renders the supplier detail with item breakdown and total spend', function () {
    $unit = Unit::create(['nama' => 'batang']);
    $item = Item::create(['nama' => 'Besi', 'unit_id' => $unit->id]);
    $supplier = Supplier::create(['nama' => 'UD. Harta Ayu']);

    seedNota($supplier, 'NOTA-1', '300000.00', [
        ['deskripsi' => 'Besi', 'item_id' => $item->id, 'unit_id' => $unit->id, 'qty' => '2.00', 'net_total' => '200000.00'],
        ['deskripsi' => 'Paku', 'qty' => '1.00', 'net_total' => '100000.00'],
    ]);
    seedNota($supplier, 'NOTA-2', '150000.00', [
        ['deskripsi' => 'Besi', 'item_id' => $item->id, 'unit_id' => $unit->id, 'qty' => '3.00', 'net_total' => '150000.00'],
    ]);

    Livewire::actingAs(detailManager())
        ->test(SupplierShow::class, ['supplier' => $supplier])
        ->assertOk()
        ->assertSee('UD. Harta Ayu')
        ->assertSee('Besi')
        ->assertSee('Paku')
        ->assertSee('Rp 450.000')  // total belanja = 300k + 150k
        ->assertSee('Rp 350.000'); // Besi net = 200k + 150k
});

it('renders the item and unit detail pages', function () {
    $unit = Unit::create(['nama' => 'batang']);
    $item = Item::create(['nama' => 'Besi', 'unit_id' => $unit->id]);
    $supplier = Supplier::create(['nama' => 'UD. Harta Ayu']);
    seedNota($supplier, 'NOTA-3', '200000.00', [
        ['deskripsi' => 'Besi', 'item_id' => $item->id, 'unit_id' => $unit->id, 'qty' => '2.00', 'net_total' => '200000.00'],
    ]);

    Livewire::actingAs(detailManager())
        ->test(ItemShow::class, ['item' => $item])
        ->assertOk()
        ->assertSee('Besi')
        ->assertSee('Rp 200.000');

    Livewire::actingAs(detailManager())
        ->test(UnitShow::class, ['unit' => $unit])
        ->assertOk()
        ->assertSee('batang')
        ->assertSee('Besi');
});

it('blocks non-managers from master detail routes', function () {
    $supplier = Supplier::create(['nama' => 'X']);
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    // RoleMiddleware mengalihkan user non-manager ke dashboard mereka sendiri.
    $this->actingAs($user)->get(route('admin.suppliers.show', $supplier))
        ->assertRedirect(route('user.dashboard'));
});

it('redirects guests from master detail routes to login', function () {
    $supplier = Supplier::create(['nama' => 'X']);

    $this->get(route('admin.suppliers.show', $supplier))->assertRedirect(route('login'));
});
