<?php

namespace App\Livewire\Guest;

use App\Actions\Report\SearchCatalog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Overlay pencarian global halaman guest (read-only). Tidak memutasi data —
 * hanya men-dispatch event agar PurchaseBrowser menyetel filter atau membuka
 * modal detail nota. Kueri didelegasikan ke Action SearchCatalog.
 */
class GlobalSearch extends Component
{
    public bool $open = false;

    public string $q = '';

    #[On('open-guest-search')]
    public function openSearch(): void
    {
        $this->open = true;
    }

    public function pickSupplier(int $id): void
    {
        $this->dispatch('guest-filter-supplier', id: $id);
        $this->close();
    }

    public function pickItem(string $nama): void
    {
        $this->dispatch('guest-search-items', term: $nama);
        $this->close();
    }

    public function pickNote(int $id): void
    {
        $this->dispatch('open-purchase-detail', purchaseId: $id);
        $this->close();
    }

    private function close(): void
    {
        $this->open = false;
        $this->q = '';
    }

    public function render(SearchCatalog $search): View
    {
        $term = trim($this->q);

        $results = strlen($term) >= 2
            ? $search->execute($term)
            : ['suppliers' => collect(), 'items' => collect(), 'notes' => collect()];

        return view('livewire.guest.global-search', [
            'results' => $results,
            'hasQuery' => strlen($term) >= 2,
        ]);
    }
}
