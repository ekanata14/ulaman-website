<?php

use App\Models\Purchase;
use App\Models\PurchaseItem;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\PurchaseImportSeeder;
use Database\Seeders\SupplierSeeder;
use Database\Seeders\UnitSeeder;

beforeEach(function () {
    $this->seed(UnitSeeder::class);
    $this->seed(SupplierSeeder::class);
    $this->seed(AdminUserSeeder::class);
    $this->seed(PurchaseImportSeeder::class);
});

it('seeds the Ulaman Renovation data accurately and completely', function () {
    // Total nominal harus PERSIS sama dengan jumlah seluruh baris Total di Excel
    // (760.319.515) — bukti tidak ada baris yang terlewat atau terhitung ganda.
    expect(bccomp(Purchase::query()->sum('grand_total'), '760319515.00', 2))->toBe(0);

    expect(Purchase::query()->count())->toBe(267);
    expect(PurchaseItem::query()->count())->toBeGreaterThanOrEqual(500);

    // Baris qty×harga ≠ Total ditandai untuk ditinjau, bukan dibuang.
    expect(Purchase::query()->where('needs_review', true)->count())->toBeGreaterThanOrEqual(3);
});

it('is idempotent — re-running does not duplicate notes', function () {
    $count = Purchase::query()->count();

    $this->seed(PurchaseImportSeeder::class);

    expect(Purchase::query()->count())->toBe($count);
});
