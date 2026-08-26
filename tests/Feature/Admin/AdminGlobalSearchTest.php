<?php

use App\Actions\Report\AdminGlobalSearch;
use App\Enums\PurchaseStatus;
use App\Livewire\Admin\GlobalSearch;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

function gsSuperAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
}

function gsPurchase(string $kode, string $status, ?string $nomorNota = null): Purchase
{
    $p = new Purchase;
    $p->forceFill([
        'kode' => $kode,
        'nomor_nota' => $nomorNota,
        'tanggal' => now(),
        'status' => $status,
        'grand_total' => 1000,
        'subtotal' => 1000,
        'total_diskon_item' => 0,
        'total_diskon_bundle' => 0,
        'diskon_nota_nilai' => 0,
        'diskon_nota_amount' => 0,
    ])->save();

    return $p;
}

function gsSeed(): void
{
    Supplier::create(['nama' => 'Yoyo Jaya', 'is_active' => true]);
    Supplier::create(['nama' => 'Yo Nonaktif', 'is_active' => false]);
    Item::create(['nama' => 'Yoyo String']);
    Unit::create(['nama' => 'Yoyo', 'simbol' => 'yo']);
    User::factory()->create(['name' => 'Yolanda', 'role' => 'super_admin', 'is_active' => true]);
    User::factory()->create(['name' => 'Yosua', 'role' => 'admin', 'is_active' => true]);
    gsPurchase('PB-YO-DRAFT', PurchaseStatus::DRAFT->value, 'YO-001');
    gsPurchase('PB-YO-FINAL', PurchaseStatus::FINAL->value, 'YO-002');
}

it('AdminGlobalSearch mencakup semua entitas termasuk draft & supplier nonaktif', function () {
    gsSeed();

    $r = app(AdminGlobalSearch::class)->execute('yo');

    expect($r['suppliers']->pluck('nama'))->toContain('Yoyo Jaya')->toContain('Yo Nonaktif')
        ->and($r['items']->pluck('nama'))->toContain('Yoyo String')
        ->and($r['units']->pluck('nama'))->toContain('Yoyo')
        ->and($r['users']->pluck('name'))->toContain('Yolanda')->toContain('Yosua')
        ->and($r['purchases']->pluck('kode'))->toContain('PB-YO-DRAFT')->toContain('PB-YO-FINAL');
});

it('AdminGlobalSearch menyaring user berdasarkan role', function () {
    gsSeed();

    $r = app(AdminGlobalSearch::class)->execute('yo', 'admin');

    expect($r['users']->pluck('name'))->toContain('Yosua')->not->toContain('Yolanda');
});

it('halaman global search menampilkan hasil terkelompok', function () {
    gsSeed();

    Livewire::actingAs(gsSuperAdmin())->test(GlobalSearch::class)
        ->set('search', 'yo')
        ->assertOk()
        ->assertSee('Yoyo Jaya')
        ->assertSee('Yoyo String')
        ->assertSee('Yolanda')
        ->assertSee('PB-YO-DRAFT');
});

it('halaman global search meminta minimal 2 karakter', function () {
    Livewire::actingAs(gsSuperAdmin())->test(GlobalSearch::class)
        ->set('search', 'y')
        ->assertSee('Type at least 2 characters to search.');
});

it('route global search hanya untuk super admin', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    $this->actingAs($admin)->get(route('admin.global-search', ['q' => 'yo']))
        ->assertForbidden();
});
