<?php

namespace App\Livewire\Admin;

use App\Actions\Report\AdminGlobalSearch as AdminGlobalSearchAction;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * §11 — Pencarian global admin: satu kotak pencarian, hasil dikelompokkan per
 * entitas (nota, supplier, barang, satuan, user). Query didelegasikan ke
 * AdminGlobalSearch. Tiap baris hasil deep-link ke halaman entitasnya.
 */
#[Layout('layouts.app')]
#[Title('Global Search')]
class GlobalSearch extends Component
{
    #[Url(as: 'q', history: true)]
    public string $search = '';

    public string $filterUserRole = '';

    public function render(AdminGlobalSearchAction $search): View
    {
        $term = trim($this->search);
        $hasQuery = mb_strlen($term) >= 2;

        $results = $hasQuery
            ? $search->execute($term, $this->filterUserRole)
            : ['purchases' => collect(), 'suppliers' => collect(), 'items' => collect(), 'units' => collect(), 'users' => collect()];

        $total = collect($results)->sum(fn ($group): int => $group->count());

        return view('livewire.admin.global-search', [
            'results' => $results,
            'hasQuery' => $hasQuery,
            'total' => $total,
        ]);
    }
}
