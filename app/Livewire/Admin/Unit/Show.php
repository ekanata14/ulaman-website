<?php

namespace App\Livewire\Admin\Unit;

use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman detail satuan (§11.4): daftar barang yang memakai satuan ini.
 * Tanpa agregasi uang — hanya penyajian daftar.
 */
#[Layout('layouts.app')]
class Show extends Component
{
    use AuthorizesRequests, WithPagination;

    public Unit $unit;

    public function mount(Unit $unit): void
    {
        $this->authorize('view', $unit);
        $this->unit = $unit;
    }

    public function render(): View
    {
        return view('livewire.admin.unit.show', [
            'items' => $this->unit->items()
                ->with('supplierTerakhir')
                ->orderBy('nama')
                ->paginate(15),
        ]);
    }
}
