<?php

namespace App\Livewire\Admin\Supplier;

use App\Actions\Report\GetSupplierPurchaseDetail;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman detail supplier (§11.4): profil, total belanja, dan rincian barang
 * yang dibeli. Komponen tipis — agregasi uang ditangani Query Action.
 */
#[Layout('layouts.app')]
class Show extends Component
{
    use AuthorizesRequests, WithPagination;

    public Supplier $supplier;

    public function mount(Supplier $supplier): void
    {
        $this->authorize('view', $supplier);
        $this->supplier = $supplier;
    }

    public function render(): View
    {
        return view('livewire.admin.supplier.show', [
            'detail' => app(GetSupplierPurchaseDetail::class)->execute($this->supplier),
            'recent' => $this->supplier->purchases()
                ->latest('tanggal')
                ->paginate(10),
        ]);
    }
}
