<?php

use App\Livewire\Admin\Import\Wizard;
use App\Models\User;
use Livewire\Livewire;

it('menampilkan wizard impor untuk admin (langkah 1)', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    Livewire::actingAs($admin)
        ->test(Wizard::class)
        ->assertOk()
        ->assertSet('step', 1);
});

it('mengarahkan guest ke login untuk route impor', function () {
    $this->get(route('admin.import'))->assertRedirect(route('login'));
});

it('mengunduh contoh template excel untuk admin', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    Livewire::actingAs($admin)
        ->test(Wizard::class)
        ->call('downloadTemplate')
        ->assertFileDownloaded('template-import-ulaman.xlsx');
});
