<?php

namespace App\Livewire\Admin\Item;

use App\Actions\Report\GetItemPurchaseSummary;
use App\Models\Item;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman detail barang (§11.4): profil, ringkasan belanja, dan riwayat
 * pembelian per baris nota. Komponen tipis — agregasi uang di Query Action.
 */
#[Layout('layouts.app')]
class Show extends Component
{
    use AuthorizesRequests, WithPagination;

    public Item $item;

    public function mount(Item $item): void
    {
        $this->authorize('view', $item);
        $this->item = $item->load('unit', 'supplierTerakhir');
    }

    public function render(): View
    {
        return view('livewire.admin.item.show', [
            'summary' => app(GetItemPurchaseSummary::class)->execute($this->item),
            'history' => $this->item->purchaseItems()
                ->with(['purchase.supplier', 'unit'])
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->orderByDesc('purchases.tanggal')
                ->select('purchase_items.*')
                ->paginate(15),
        ]);
    }
}
