<?php

namespace App\Livewire\Admin;

use App\Actions\Report\GetMonthlyTrend;
use App\Actions\Report\GetPurchaseSummary;
use App\Actions\Report\GetSupplierRanking;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Models\Purchase;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * §F-07 — Dashboard admin UPL: ringkasan all-time, tren bulanan, peringkat
 * supplier, dan nota terbaru. Komponen tipis: hanya otorisasi + memanggil
 * Query Action + render.
 */
#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('viewAny', Purchase::class);
    }

    public function render(): View
    {
        $filter = new PurchaseFilterData;

        return view('livewire.admin.dashboard', [
            'summary' => app(GetPurchaseSummary::class)->execute($filter),
            'trend' => app(GetMonthlyTrend::class)->execute($filter),
            'ranking' => app(GetSupplierRanking::class)->execute($filter, 5),
            'recent' => Purchase::query()->final()->with('supplier')->latest('tanggal')->limit(8)->get(),
        ]);
    }
}
