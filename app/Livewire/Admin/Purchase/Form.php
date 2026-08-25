<?php

namespace App\Livewire\Admin\Purchase;

use App\Actions\Calculation\CalculatePurchaseTotals;
use App\Actions\Category\StoreCategory;
use App\Actions\Item\StoreItem;
use App\Actions\Purchase\DeletePurchase;
use App\Actions\Purchase\StorePurchase;
use App\Actions\Purchase\UpdatePurchase;
use App\Actions\Supplier\StoreSupplier;
use App\Actions\Unit\StoreUnit;
use App\Concerns\WithConfirmation;
use App\DTOs\Category\CategoryData;
use App\DTOs\Item\ItemData;
use App\DTOs\Purchase\CalculatedPurchaseData;
use App\DTOs\Supplier\SupplierData;
use App\DTOs\Unit\UnitData;
use App\Enums\BundleType;
use App\Livewire\Forms\PurchaseForm;
use App\Models\Category;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

/**
 * §11.3 — Jantung aplikasi. Komponen HANYA: otorisasi, validasi bentuk, rakit
 * DTO, panggil Action, render. TIDAK menghitung apa pun (§9.1). Angka final
 * dari CalculatePurchaseTotals di server. Bundle & Foto menyusul di Fase 3.
 */
#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests, Toast, WithConfirmation;

    public PurchaseForm $form;

    public ?Purchase $purchase = null;

    public bool $deleteModalOpen = false;

    /** @var array<int, string> UID item yang dicentang untuk membentuk bundle. */
    public array $selectedForBundle = [];

    public string $bundleNama = '';

    public string $bundleTipe = 'PERSEN';

    public string $bundleNilai = '0';

    // --- QUICK-ADD: Supplier ---
    public bool $supplierModalOpen = false;

    public string $qsNama = '';

    public ?string $qsPic = null;

    public ?string $qsTelepon = null;

    // --- QUICK-ADD: Kategori ---
    public bool $categoryModalOpen = false;

    public string $qcNama = '';

    public ?string $qcWarna = null;

    // --- QUICK-ADD: Satuan (per baris) ---
    public bool $unitModalOpen = false;

    public ?int $unitTargetIndex = null;

    public string $quNama = '';

    public ?string $quSimbol = null;

    // --- QUICK-ADD: Master Item (per baris) ---
    public bool $itemModalOpen = false;

    public ?int $itemTargetIndex = null;

    public string $qiNama = '';

    public ?int $qiUnitId = null;

    public ?int $qiCategoryId = null;

    public function mount(?Purchase $purchase = null): void
    {
        if ($purchase !== null && $purchase->exists) {
            $this->authorize('update', $purchase);
            $purchase->load(['items', 'bundles.bundleItems']);
            $this->purchase = $purchase;
            $this->form->setPurchase($purchase);

            return;
        }

        $this->authorize('create', Purchase::class);
        $this->form->tanggal = now()->format('Y-m-d');
        $this->addItem();
    }

    public function addItem(): void
    {
        $this->form->items[] = [
            'uid' => (string) Str::uuid(),
            'id' => null,
            'itemId' => null,
            'deskripsi' => '',
            'qty' => '',
            'unitId' => null,
            'hargaSatuan' => '',
            'diskonTipe' => 'NONE',
            'diskonNilai' => '0',
            'remark' => null,
        ];
    }

    public function confirmRemoveItem(string $uid): void
    {
        $this->askConfirm(
            'removeItem',
            [$uid],
            __('Remove this item from the note?'),
            '',
            true,
            'o-trash',
            __('Yes, Remove'),
        );
    }

    public function removeItem(string $uid): void
    {
        $this->form->items = array_values(
            array_filter($this->form->items, static fn (array $row): bool => $row['uid'] !== $uid),
        );
        $this->selectedForBundle = array_values(array_diff($this->selectedForBundle, [$uid]));
        $this->dissolveBundlesMissing($uid);
        $this->info(__('Item removed.'));
    }

    public function confirmCreateBundle(): void
    {
        $this->askConfirm(
            'createBundle',
            [],
            __('Create this bundle?'),
            '',
            false,
            'o-cube',
            __('Yes, Create'),
        );
    }

    public function createBundle(): void
    {
        $itemUids = array_map(static fn (array $r): string => $r['uid'], $this->form->items);
        $members = array_values(array_intersect($itemUids, $this->selectedForBundle));

        if (count($members) < 2) {
            $this->error(__('A bundle needs at least 2 items.'));

            return;
        }

        if (array_intersect($members, $this->bundledUids()) !== []) {
            $this->error(__('An item can belong to at most one bundle.'));

            return;
        }

        $this->validate([
            'bundleNama' => ['required', 'string', 'max:255'],
            'bundleTipe' => ['required', Rule::enum(BundleType::class)],
            'bundleNilai' => ['required', 'numeric', 'min:0'],
        ]);

        $this->form->bundles[] = [
            'uid' => (string) Str::uuid(),
            'nama' => $this->bundleNama,
            'tipe' => $this->bundleTipe,
            'nilai' => $this->bundleNilai,
            'itemUids' => $members,
        ];

        $this->reset('selectedForBundle', 'bundleNama', 'bundleNilai');
        $this->bundleTipe = 'PERSEN';
        $this->success(__('Bundle created.'));
    }

    public function confirmRemoveBundle(string $uid): void
    {
        $this->askConfirm(
            'removeBundle',
            [$uid],
            __('Dissolve this bundle?'),
            '',
            true,
            'o-trash',
            __('Yes, Dissolve'),
        );
    }

    public function removeBundle(string $uid): void
    {
        $this->form->bundles = array_values(
            array_filter($this->form->bundles, static fn (array $b): bool => $b['uid'] !== $uid),
        );
        $this->info(__('Bundle dissolved.'));
    }

    /**
     * @return array<int, string>
     */
    private function bundledUids(): array
    {
        $uids = [];
        foreach ($this->form->bundles as $bundle) {
            $uids = array_merge($uids, $bundle['itemUids']);
        }

        return $uids;
    }

    private function dissolveBundlesMissing(string $removedUid): void
    {
        $kept = [];
        foreach ($this->form->bundles as $bundle) {
            $members = array_values(array_diff($bundle['itemUids'], [$removedUid]));
            if (count($members) < 2) {
                $this->warning(__('Bundle ":name" dissolved (fewer than 2 members).', ['name' => $bundle['nama']]));

                continue;
            }
            $bundle['itemUids'] = $members;
            $kept[] = $bundle;
        }
        $this->form->bundles = $kept;
    }

    /**
     * Auto-isi deskripsi/satuan/harga terakhir saat item master dipilih.
     */
    public function updated(string $name): void
    {
        if (preg_match('/^form\.items\.(\d+)\.itemId$/', $name, $m) !== 1) {
            return;
        }

        $idx = (int) $m[1];
        $itemId = $this->form->items[$idx]['itemId'] ?? null;
        if ($itemId === null || $itemId === '') {
            return;
        }

        $this->applyMasterItem($idx, (int) $itemId);
    }

    /**
     * Auto-isi deskripsi/satuan/harga terakhir dari item master ke baris ke-$idx.
     */
    private function applyMasterItem(int $idx, int $itemId): void
    {
        $item = Item::find($itemId);
        if ($item === null || ! isset($this->form->items[$idx])) {
            return;
        }

        $this->form->items[$idx]['deskripsi'] = $item->nama;
        $this->form->items[$idx]['unitId'] = $item->unit_id;
        if (($this->form->items[$idx]['hargaSatuan'] ?? '') === '' && $item->harga_terakhir !== null) {
            $this->form->items[$idx]['hargaSatuan'] = (string) $item->harga_terakhir;
        }
    }

    // ==================== QUICK-ADD MASTER DATA ====================

    public function openSupplierModal(): void
    {
        $this->authorize('create', Supplier::class);
        $this->reset('qsNama', 'qsPic', 'qsTelepon');
        $this->supplierModalOpen = true;
    }

    public function confirmSaveSupplier(): void
    {
        $this->validate([
            'qsNama' => ['required', 'string', 'max:255', 'unique:suppliers,nama'],
            'qsPic' => ['nullable', 'string', 'max:255'],
            'qsTelepon' => ['nullable', 'string', 'max:255'],
        ]);
        $this->askConfirm('saveSupplier', [], __('Save this supplier?'), '', false, 'o-check-circle', __('Yes, Save'));
    }

    public function saveSupplier(): void
    {
        $this->authorize('create', Supplier::class);
        $supplier = app(StoreSupplier::class)->execute(new SupplierData(
            id: null,
            nama: $this->qsNama,
            telepon: $this->qsTelepon,
            pic: $this->qsPic,
        ));

        $this->form->supplierId = $supplier->id;
        $this->supplierModalOpen = false;
        $this->reset('qsNama', 'qsPic', 'qsTelepon');
        $this->success(__('Supplier added.'));
    }

    public function openCategoryModal(): void
    {
        $this->authorize('create', Category::class);
        $this->reset('qcNama', 'qcWarna');
        $this->categoryModalOpen = true;
    }

    public function confirmSaveCategory(): void
    {
        $this->validate([
            'qcNama' => ['required', 'string', 'max:255'],
            'qcWarna' => ['nullable', 'string', 'max:50'],
        ]);
        $this->askConfirm('saveCategory', [], __('Save this category?'), '', false, 'o-check-circle', __('Yes, Save'));
    }

    public function saveCategory(): void
    {
        $this->authorize('create', Category::class);
        $category = app(StoreCategory::class)->execute(new CategoryData(
            id: null,
            nama: $this->qcNama,
            warna: $this->qcWarna,
        ));

        $this->form->categoryId = $category->id;
        $this->categoryModalOpen = false;
        $this->reset('qcNama', 'qcWarna');
        $this->success(__('Category added.'));
    }

    public function openUnitModal(int $index): void
    {
        $this->authorize('create', Unit::class);
        $this->unitTargetIndex = $index;
        $this->reset('quNama', 'quSimbol');
        $this->unitModalOpen = true;
    }

    public function confirmSaveUnit(): void
    {
        $this->validate([
            'quNama' => ['required', 'string', 'max:255'],
            'quSimbol' => ['nullable', 'string', 'max:50'],
        ]);
        $this->askConfirm('saveUnit', [], __('Save this unit?'), '', false, 'o-check-circle', __('Yes, Save'));
    }

    public function saveUnit(): void
    {
        $this->authorize('create', Unit::class);
        $unit = app(StoreUnit::class)->execute(new UnitData(
            id: null,
            nama: $this->quNama,
            simbol: $this->quSimbol,
        ));

        if ($this->unitTargetIndex !== null && isset($this->form->items[$this->unitTargetIndex])) {
            $this->form->items[$this->unitTargetIndex]['unitId'] = $unit->id;
        }

        $this->unitModalOpen = false;
        $this->reset('quNama', 'quSimbol', 'unitTargetIndex');
        $this->success(__('Unit added.'));
    }

    public function openItemModal(int $index): void
    {
        $this->authorize('create', Item::class);
        $this->itemTargetIndex = $index;
        $this->reset('qiNama', 'qiUnitId', 'qiCategoryId');
        $this->itemModalOpen = true;
    }

    public function confirmSaveItem(): void
    {
        $this->validate([
            'qiNama' => ['required', 'string', 'max:255', 'unique:items,nama'],
            'qiUnitId' => ['nullable', 'exists:units,id'],
            'qiCategoryId' => ['nullable', 'exists:categories,id'],
        ]);
        $this->askConfirm('saveItem', [], __('Save this item?'), '', false, 'o-check-circle', __('Yes, Save'));
    }

    public function saveItem(): void
    {
        $this->authorize('create', Item::class);
        $item = app(StoreItem::class)->execute(new ItemData(
            id: null,
            nama: $this->qiNama,
            unitId: $this->qiUnitId,
            categoryId: $this->qiCategoryId,
        ));

        if ($this->itemTargetIndex !== null && isset($this->form->items[$this->itemTargetIndex])) {
            $this->form->items[$this->itemTargetIndex]['itemId'] = $item->id;
            $this->applyMasterItem($this->itemTargetIndex, $item->id);
        }

        $this->itemModalOpen = false;
        $this->reset('qiNama', 'qiUnitId', 'qiCategoryId', 'itemTargetIndex');
        $this->success(__('Item added.'));
    }

    protected function validateForSave(): void
    {
        $this->form->validate();
    }

    public function save(): void
    {
        $this->authorize(
            $this->form->purchaseId !== null ? 'update' : 'create',
            $this->form->purchaseId !== null ? $this->purchase : Purchase::class,
        );

        $this->form->validate();
        $dto = $this->form->toDto();

        if ($this->form->purchaseId !== null && $this->purchase !== null) {
            app(UpdatePurchase::class)->execute($this->purchase, $dto, $this->actor());
            $this->success(__('Purchase note updated.'));
        } else {
            app(StorePurchase::class)->execute($dto, $this->actor());
            $this->success(__('Purchase note saved.'));
        }

        $this->redirectRoute('admin.purchases', navigate: true);
    }

    public function confirmDelete(): void
    {
        $this->deleteModalOpen = true;
    }

    public function delete(): void
    {
        if ($this->purchase === null) {
            return;
        }

        $this->authorize('delete', $this->purchase);
        app(DeletePurchase::class)->execute($this->purchase, $this->actor());
        $this->success(__('Purchase note deleted.'));
        $this->redirectRoute('admin.purchases', navigate: true);
    }

    /**
     * Pratinjau alokasi & grand total memakai kalkulator server (§9.1) —
     * komponen tidak menghitung sendiri. Null bila data belum lengkap.
     */
    private function preview(): ?CalculatedPurchaseData
    {
        try {
            return app(CalculatePurchaseTotals::class)->execute($this->form->toDto());
        } catch (\Throwable) {
            return null;
        }
    }

    public function render(): View
    {
        return view('livewire.admin.purchase.form', [
            'preview' => $this->preview(),
            'suppliers' => Supplier::query()->active()->orderBy('nama')
                ->get()->map(fn (Supplier $s): array => ['id' => $s->id, 'name' => $s->nama])->all(),
            'categories' => Category::query()->orderBy('nama')
                ->get()->map(fn (Category $c): array => ['id' => $c->id, 'name' => $c->nama])->all(),
            'units' => Unit::query()->orderBy('nama')
                ->get()->map(fn (Unit $u): array => ['id' => $u->id, 'name' => $u->nama])->all(),
            'items' => Item::query()->orderBy('nama')
                ->get()->map(fn (Item $i): array => ['id' => $i->id, 'name' => $i->nama])->all(),
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
