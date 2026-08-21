<?php

namespace App\Livewire\Admin\AuditLog;

use App\Models\AuditLog;
use App\Models\Purchase;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * §F-10 — Audit log admin. READ-ONLY: tidak ada method mutasi (create/update/
 * delete). Hanya menampilkan riwayat aksi dengan filter. Eager load user
 * (anti N+1). Otorisasi reuse PurchasePolicy (viewAny).
 */
#[Layout('layouts.app')]
#[Title('Audit Log')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterAksi = '';

    #[Url(as: 'dari', history: true)]
    public ?string $dari = null;

    #[Url(as: 'sampai', history: true)]
    public ?string $sampai = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Purchase::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filterAksi', 'dari', 'sampai'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterAksi', 'dari', 'sampai']);
        $this->resetPage();
    }

    public function render(): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($this->search !== '', function (Builder $q): void {
                $term = $this->search;
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('aksi', 'like', "%{$term}%")
                        ->orWhere('auditable_type', 'like', "%{$term}%");
                });
            })
            ->when($this->filterAksi !== '', fn (Builder $q) => $q->where('aksi', $this->filterAksi))
            ->when($this->dari !== null && $this->dari !== '', fn (Builder $q) => $q->whereDate('created_at', '>=', $this->dari))
            ->when($this->sampai !== null && $this->sampai !== '', fn (Builder $q) => $q->whereDate('created_at', '<=', $this->sampai))
            ->latest('created_at')
            ->paginate(20);

        return view('livewire.admin.audit-log.index', [
            'logs' => $logs,
        ]);
    }
}
