<?php

use App\Actions\Report\GetMonthlyTrend;
use App\Actions\Report\GetSupplierRanking;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Livewire\Admin\AuditLog\Index as AuditLogIndex;
use App\Livewire\Admin\Dashboard;
use App\Models\User;
use Livewire\Livewire;

function admin(): User
{
    return User::factory()->create(['role' => 'admin', 'is_active' => true]);
}

it('renders the admin dashboard', function () {
    Livewire::actingAs(admin())
        ->test(Dashboard::class)
        ->assertOk();
});

it('renders the admin audit log index', function () {
    Livewire::actingAs(admin())
        ->test(AuditLogIndex::class)
        ->assertOk();
});

it('returns arrays from report actions on an empty database', function () {
    $filter = new PurchaseFilterData;

    $trend = app(GetMonthlyTrend::class)->execute($filter);
    $ranking = app(GetSupplierRanking::class)->execute($filter, 5);

    expect($trend)->toBeArray()
        ->and($trend)->toBe([])
        ->and($ranking)->toBeArray()
        ->and($ranking)->toBe([]);
});
