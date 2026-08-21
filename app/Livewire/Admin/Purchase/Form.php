<?php

namespace App\Livewire\Admin\Purchase;

use App\Actions\Calculation\CalculatePurchaseTotals;
use App\Actions\Purchase\DeletePurchase;
use App\Actions\Purchase\StorePurchase;
use App\Actions\Purchase\UpdatePurchase;
use App\DTOs\Purchase\CalculatedPurchaseData;
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
    use AuthorizesRequests, Toast;

    public PurchaseForm $form;

    public ?Purchase $purchase = null;

    public bool $deleteModalOpen = false;

    /** @var array<int, string> UID item yang dicentang untuk membentuk bundle. */
    public array $selectedForBundle = [];

    public string $bundleNama = '';

    public string $bundleTipe = 'PERSEN';

    public string $bundleNilai = '0';

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

    public function removeItem(string $uid): void
    {
        $this->form->items = array_values(
            array_filter($this->form->items, static fn (array $row): bool => $row['uid'] !== $uid),
        );
        $this->selectedForBundle = array_values(array_diff($this->selectedForBundle, [$uid]));
        $this->dissolveBundlesMissing($uid);
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

    public function removeBundle(string $uid): void
    {
        $this->form->bundles = array_values(
            array_filter($this->form->bundles, static fn (array $b): bool => $b['uid'] !== $uid),
        );
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

        $item = Item::find((int) $itemId);
        if ($item === null) {
            return;
        }

        $this->form->items[$idx]['deskripsi'] = $item->nama;
        $this->form->items[$idx]['unitId'] = $item->unit_id;
        if (($this->form->items[$idx]['hargaSatuan'] ?? '') === '' && $item->harga_terakhir !== null) {
            $this->form->items[$idx]['hargaSatuan'] = (string) $item->harga_terakhir;
        }
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
