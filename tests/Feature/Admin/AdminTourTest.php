<?php

use App\Livewire\AdminTour;
use App\Models\User;
use Livewire\Livewire;

it('menandai tour selesai di preferences saat event tour-finished', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
        'preferences' => ['locale_hint' => 'id'],
    ]);

    Livewire::actingAs($admin)
        ->test(AdminTour::class)
        ->dispatch('tour-finished');

    $admin->refresh();

    expect($admin->preferences['tour_completed'])->toBeTrue()
        // Preferensi lain tidak boleh terhapus.
        ->and($admin->preferences['locale_hint'])->toBe('id');
});

it('menyetel ulang status onboarding dan meminta tour dimulai lagi', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
        'preferences' => ['tour_completed' => true],
    ]);

    Livewire::actingAs($admin)
        ->test(AdminTour::class)
        ->call('resetTour')
        ->assertDispatched('admin-tour:start');

    $admin->refresh();

    expect($admin->preferences['tour_completed'])->toBeFalse();
});

it('mengaktifkan auto-start di dashboard untuk admin yang belum menyelesaikan tour', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('"autoStart":true', false);
});

it('tidak mengaktifkan auto-start setelah tour diselesaikan', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
        'preferences' => ['tour_completed' => true],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('"autoStart":false', false);
});

it('tidak pernah auto-start di luar dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    $this->actingAs($admin)
        ->get(route('admin.purchases'))
        ->assertOk()
        ->assertSee('"autoStart":false', false);
});
