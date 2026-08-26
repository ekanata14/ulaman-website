<?php

namespace App\Livewire\Admin\Purchase;

use App\Actions\Export\ExportPurchasesToCsv;
use App\Actions\Export\ExportPurchasesToExcel;
use App\Actions\Export\GeneratePurchasePdf;
use App\Actions\Purchase\DeletePurchase;
use App\Actions\Purchase\DuplicatePurchase;
use App\Concerns\WithConfirmation;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Symfony\Component\HttpFoundation\Response;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests, Toast, WithConfirmation, WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $dari = '';

    #[Url(history: true)]
    public string $sampai = '';

    #[Url(history: true)]
    public ?int $supplierId = null;

    #[Url(history: true)]
    public string $status = '';

    public bool $deleteModalOpen = false;

    public ?int $toDeleteId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Purchase::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'dari', 'sampai', 'supplierId', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'dari', 'sampai', 'supplierId', 'status']);
        $this->resetPage();
    }

    public function confirmDuplicate(int $id): void
    {
        $this->askConfirm(
            'duplicate',
            [$id],
            __('Duplicate this purchase item?'),
            __('A copy will be created that you can edit.'),
            false,
            'o-document-duplicate',
            __('Yes, Duplicate'),
        );
    }

    public function duplicate(int $id): void
    {
        $this->authorize('create', Purchase::class);
        $purchase = Purchase::findOrFail($id);
        $copy = app(DuplicatePurchase::class)->execute($purchase, $this->actor());
        $this->success(__('Purchase item duplicated.'));
        $this->redirectRoute('admin.purchases.edit', ['purchase' => $copy->id], navigate: true);
    }

    public function confirmDelete(int $id): void
    {
        $this->toDeleteId = $id;
        $this->deleteModalOpen = true;
    }

    public function delete(): void
    {
        if ($this->toDeleteId !== null) {
            $purchase = Purchase::findOrFail($this->toDeleteId);
            $this->authorize('delete', $purchase);
            app(DeletePurchase::class)->execute($purchase, $this->actor());
            $this->success(__('Purchase item deleted.'));
        }

        $this->deleteModalOpen = false;
    }

    public function exportCsv(): Response
    {
        $this->authorize('viewAny', Purchase::class);

        return app(ExportPurchasesToCsv::class)->execute($this->exportFilter());
    }

    public function exportExcel(): Response
    {
        $this->authorize('viewAny', Purchase::class);

        return app(ExportPurchasesToExcel::class)->execute($this->exportFilter());
    }

    public function exportPdf(): Response
    {
        $this->authorize('viewAny', Purchase::class);

        return app(GeneratePurchasePdf::class)->execute($this->exportFilter());
    }

    private function exportFilter(): PurchaseFilterData
    {
        return new PurchaseFilterData(
            dari: $this->dari !== '' ? $this->dari : null,
            sampai: $this->sampai !== '' ? $this->sampai : null,
            supplierIds: $this->supplierId !== null ? [$this->supplierId] : [],
            search: $this->search !== '' ? $this->search : null,
        );
    }

    public function render(): View
    {
        $query = Purchase::query()->with('supplier')->withCount('items');

        if ($this->search !== '') {
            $query->search($this->search);
        }
        if ($this->dari !== '') {
            $query->whereDate('tanggal', '>=', $this->dari);
        }
        if ($this->sampai !== '') {
            $query->whereDate('tanggal', '<=', $this->sampai);
        }
        if ($this->supplierId !== null) {
            $query->where('supplier_id', $this->supplierId);
        }
        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return view('livewire.admin.purchase.index', [
            'purchases' => $query->latest('tanggal')->latest('id')->paginate(15),
            'suppliers' => Supplier::query()->orderBy('nama')
                ->get()->map(fn (Supplier $s): array => ['id' => $s->id, 'name' => $s->nama])->all(),
            'statusOptions' => array_map(
                static fn (PurchaseStatus $s): array => ['id' => $s->value, 'name' => $s->label()],
                PurchaseStatus::cases(),
            ),
        ]);
    }

    private function actor(): User
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
