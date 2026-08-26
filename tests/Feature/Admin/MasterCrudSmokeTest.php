<?php

use App\Livewire\Admin\Item\Index as ItemIndex;
use App\Livewire\Admin\Supplier\Index as SupplierIndex;
use App\Livewire\Admin\Unit\Index as UnitIndex;
use App\Models\User;
use Livewire\Livewire;

function manager(): User
{
    return User::factory()->create(['role' => 'admin', 'is_active' => true]);
}

it('merender semua komponen CRUD master tanpa error', function () {
    $user = manager();

    Livewire::actingAs($user)->test(SupplierIndex::class)->assertOk();
    Livewire::actingAs($user)->test(ItemIndex::class)->assertOk();
    Livewire::actingAs($user)->test(UnitIndex::class)->assertOk();
});

it('mengarahkan guest ke login untuk route master', function () {
    $this->get(route('admin.suppliers'))->assertRedirect(route('login'));
    $this->get(route('admin.items'))->assertRedirect(route('login'));
    $this->get(route('admin.units'))->assertRedirect(route('login'));
});
