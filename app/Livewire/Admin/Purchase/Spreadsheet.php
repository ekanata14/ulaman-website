<?php

namespace App\Livewire\Admin\Purchase;

use App\Actions\Purchase\BuildPurchaseData;
use App\Actions\Purchase\DeletePurchase;
use App\Actions\Purchase\UpdatePurchase;
use App\Actions\Report\GetPurchaseNotasForSpreadsheet;
use App\Concerns\WithConfirmation;
use App\DTOs\Purchase\PurchaseData;
use App\DTOs\Purchase\PurchaseFilterData;
use App\DTOs\Purchase\PurchaseItemData;
use App\DTOs\Supplier\SupplierData;
use App\Enums\DiscountType;
use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mary\Traits\Toast;

/**
 * §11.1 (mode alternatif) — Editor "Spreadsheet": tampilan datar per-item (grup
 * per nota) dengan CRUD inline. Komponen TIDAK menghitung apa pun; tiap edit
 * merakit PurchaseData lengkap (BuildPurchaseData) lalu memanggil Store/Update/
 * DeletePurchase (§9.1). Paginasi PER-NOTA + infinite scroll (loadMore).
 * Mendukung mode fullscreen/terfokus (layout tanpa sidebar/navbar; nav via dropdown).
 */
class Spreadsheet extends Component
{
    use AuthorizesRequests, Toast, WithConfirmation;

    public bool $fullscreen = false;

    // --- FILTER ---
    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public ?int $supplierId = null;

    #[Url(history: true)]
    public string $status = '';

    /** all | range | month | year */
    #[Url(history: true)]
    public string $periodMode = 'month';

    #[Url(history: true)]
    public ?int $year = null;

    #[Url(history: true)]
    public ?int $month = null;

    #[Url(history: true)]
    public ?string $dari = null;

    #[Url(history: true)]
    public ?string $sampai = null;

    // --- INFINITE SCROLL ---
    public int $limit = 20;

    // --- MODAL FORM LENGKAP (tambah/edit di halaman ini, tanpa pindah) ---
    public bool $formModalOpen = false;

    public ?int $editingPurchaseId = null;

    public int $formInstanceKey = 0;

    public function mount(): void
    {
        $this->authorize('viewAny', Purchase::class);
        $this->fullscreen = request()->boolean('focus');
        $now = CarbonImmutable::now();
        $this->year ??= (int) $now->format('Y');
        $this->month ??= (int) $now->format('n');
    }

    public function updated(string $property): void
    {
        if (in_array($property, [
            'search', 'supplierId', 'status',
            'periodMode', 'year', 'month', 'dari', 'sampai',
        ], true)) {
            $this->limit = 20;
        }
    }

    public function loadMore(): void
    {
        $this->limit += 20;
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'supplierId', 'status', 'dari', 'sampai']);
        $this->periodMode = 'all';
        $this->limit = 20;
    }

    // ==================== CRUD INLINE ====================

    public function updateItemField(int $purchaseId, int $itemId, string $field, string $value): void
    {
        if (! in_array($field, ['deskripsi', 'qty', 'hargaSatuan', 'remark'], true)) {
            return;
        }

        if (! $this->passesItemField($field, $value)) {
            return;
        }

        $purchase = Purchase::findOrFail($purchaseId);
        $this->authorize('update', $purchase);

        $data = app(BuildPurchaseData::class)->execute($purchase);
        foreach ($data->items as $item) {
            if ($item->id !== $itemId) {
                continue;
            }
            match ($field) {
                'deskripsi' => $item->deskripsi = $value,
                'qty' => $item->qty = $value,
                'hargaSatuan' => $item->hargaSatuan = ($value === '' ? null : $value),
                'remark' => $item->remark = ($value === '' ? null : $value),
            };
        }

        app(UpdatePurchase::class)->execute($purchase, $data, $this->actor());
        $this->success(__('Saved.'));
    }

    public function updateNotaField(int $purchaseId, string $field, string $value): void
    {
        if (! in_array($field, ['tanggal', 'supplierId', 'remark'], true)) {
            return;
        }

        $purchase = Purchase::findOrFail($purchaseId);
        $this->authorize('update', $purchase);

        if ($field === 'tanggal' && ! $this->passesDate($value)) {
            return;
        }

        $data = app(BuildPurchaseData::class)->execute($purchase);
        if ($field === 'tanggal') {
            $data->tanggal = $value;
        } elseif ($field === 'supplierId') {
            $sid = $value === '' ? null : (int) $value;
            $data->supplier = $sid !== null ? new SupplierData(id: $sid, nama: '') : null;
        } else {
            $data->remark = $value === '' ? null : $value;
        }

        app(UpdatePurchase::class)->execute($purchase, $data, $this->actor());
        $this->success(__('Saved.'));
    }

    public function addItemRow(int $purchaseId): void
    {
        $purchase = Purchase::findOrFail($purchaseId);
        $this->authorize('update', $purchase);

        $data = app(BuildPurchaseData::class)->execute($purchase);
        $data->items[] = new PurchaseItemData(
            uid: (string) Str::uuid(),
            id: null,
            itemId: null,
            deskripsi: __('New item'),
            qty: '1',
            unitId: null,
            hargaSatuan: null,
            diskonTipe: DiscountType::NONE,
            diskonNilai: '0',
            remark: null,
            urutan: count($data->items),
        );

        app(UpdatePurchase::class)->execute($purchase, $data, $this->actor());
        $this->success(__('Item added.'));
    }

    public function openAddForm(): void
    {
        $this->authorize('create', Purchase::class);
        $this->editingPurchaseId = null;
        $this->formInstanceKey++;
        $this->formModalOpen = true;
    }

    public function openEditForm(int $purchaseId): void
    {
        $this->authorize('update', Purchase::findOrFail($purchaseId));
        $this->editingPurchaseId = $purchaseId;
        $this->formInstanceKey++;
        $this->formModalOpen = true;
    }

    #[On('purchase-form-saved')]
    #[On('purchase-form-cancel')]
    public function closeForm(): void
    {
        $this->formModalOpen = false;
        $this->editingPurchaseId = null;
    }

    public function confirmDeleteItem(int $purchaseId, int $itemId): void
    {
        $this->askConfirm(
            'deleteItem',
            [$purchaseId, $itemId],
            __('Remove this item?'),
            __('If it is the last line, the whole purchase item is deleted.'),
            true,
            'o-trash',
            __('Yes, Remove'),
        );
    }

    public function deleteItem(int $purchaseId, int $itemId): void
    {
        $purchase = Purchase::findOrFail($purchaseId);
        $this->authorize('update', $purchase);

        $data = app(BuildPurchaseData::class)->execute($purchase);
        $remaining = array_values(array_filter($data->items, static fn (PurchaseItemData $it): bool => $it->id !== $itemId));

        if ($remaining === []) {
            $this->authorize('delete', $purchase);
            app(DeletePurchase::class)->execute($purchase, $this->actor());
            $this->success(__('Purchase item deleted.'));

            return;
        }

        $data->items = $remaining;
        $this->cleanBundles($data);

        app(UpdatePurchase::class)->execute($purchase, $data, $this->actor());
        $this->success(__('Item removed.'));
    }

    public function confirmDeleteNota(int $purchaseId): void
    {
        $this->askConfirm(
            'deleteNota',
            [$purchaseId],
            __('Delete this purchase item?'),
            __('This will remove the purchase item and all its lines. This action cannot be undone.'),
            true,
            'o-trash',
            __('Yes, Delete'),
        );
    }

    public function deleteNota(int $purchaseId): void
    {
        $purchase = Purchase::findOrFail($purchaseId);
        $this->authorize('delete', $purchase);
        app(DeletePurchase::class)->execute($purchase, $this->actor());
        $this->success(__('Purchase item deleted.'));
    }

    /**
     * Buang uid item yang sudah hilang dari itemUids bundle; bubarkan bundle <2 anggota
     * agar konsisten dengan CalculatePurchaseTotals (mencegah referensi uid tak ada).
     */
    private function cleanBundles(PurchaseData $data): void
    {
        $existing = array_map(static fn (PurchaseItemData $it): string => (string) $it->uid, $data->items);
        $kept = [];
        $dissolved = false;
        foreach ($data->bundles as $bundle) {
            $bundle->itemUids = array_values(array_intersect($bundle->itemUids, $existing));
            if (count($bundle->itemUids) >= 2) {
                $kept[] = $bundle;
            } else {
                $dissolved = true;
            }
        }
        $data->bundles = $kept;
        if ($dissolved) {
            $this->warning(__('A bundle was dissolved (fewer than 2 members).'));
        }
    }

    private function passesItemField(string $field, string $value): bool
    {
        $rules = match ($field) {
            'deskripsi' => ['value' => ['required', 'string', 'max:255']],
            'qty' => ['value' => ['required', 'numeric', 'min:0.01']],
            'hargaSatuan' => ['value' => ['nullable', 'numeric', 'min:0']],
            'remark' => ['value' => ['nullable', 'string', 'max:255']],
            default => ['value' => []],
        };

        $validator = Validator::make(['value' => $value === '' ? null : $value], $rules);
        if ($validator->fails()) {
            $this->error($validator->errors()->first('value'));

            return false;
        }

        return true;
    }

    private function passesDate(string $value): bool
    {
        $validator = Validator::make(['value' => $value], ['value' => ['required', 'date', 'before_or_equal:tomorrow']]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first('value'));

            return false;
        }

        return true;
    }

    private function actor(): User
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function filter(): PurchaseFilterData
    {
        [$dari, $sampai] = $this->resolvePeriod();

        return new PurchaseFilterData(
            dari: $dari,
            sampai: $sampai,
            supplierIds: $this->supplierId !== null ? [$this->supplierId] : [],
            search: $this->search,
        );
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolvePeriod(): array
    {
        return match ($this->periodMode) {
            'month' => [
                sprintf('%04d-%02d-01', (int) $this->year, (int) $this->month),
                CarbonImmutable::create((int) $this->year, (int) $this->month, 1)->endOfMonth()->format('Y-m-d'),
            ],
            'year' => [sprintf('%04d-01-01', (int) $this->year), sprintf('%04d-12-31', (int) $this->year)],
            'range' => [$this->dari !== '' ? $this->dari : null, $this->sampai !== '' ? $this->sampai : null],
            default => [null, null],
        };
    }

    /**
     * @return array<int, int>
     */
    private function availableYears(): array
    {
        $min = Purchase::query()->min('tanggal');
        $max = Purchase::query()->max('tanggal');
        $now = (int) CarbonImmutable::now()->format('Y');
        $startY = $min !== null ? (int) substr((string) $min, 0, 4) : $now;
        $endY = max($max !== null ? (int) substr((string) $max, 0, 4) : $now, $now);

        return range($endY, $startY);
    }

    public function render(): View
    {
        $query = app(GetPurchaseNotasForSpreadsheet::class)->execute($this->filter(), onlyFinal: false);
        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        $totalNotas = (clone $query)->count();
        $notas = $query->take($this->limit + 1)->get();
        $hasMore = $notas->count() > $this->limit;
        $notas = $notas->take($this->limit);

        return view('livewire.admin.purchase.spreadsheet', [
            'notas' => $notas,
            'hasMore' => $hasMore,
            'totalNotas' => $totalNotas,
            'editingPurchase' => $this->editingPurchaseId !== null ? Purchase::find($this->editingPurchaseId) : null,
            'suppliers' => Supplier::query()->orderBy('nama')->get()
                ->map(fn (Supplier $s): array => ['id' => $s->id, 'name' => $s->nama])->all(),
            'statusOptions' => collect(PurchaseStatus::cases())
                ->map(fn (PurchaseStatus $s): array => ['id' => $s->value, 'name' => $s->label()])->all(),
            'years' => $this->availableYears(),
        ])->layout($this->fullscreen ? 'layouts.focus' : 'layouts.app');
    }
}
