<?php

namespace App\Livewire\Admin\Item;

use App\Actions\Item\StoreItem;
use App\Actions\Item\UpdateItem;
use App\Concerns\WithConfirmation;
use App\DTOs\Item\ItemData;
use App\Models\Category;
use App\Models\Item;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests, Toast, WithConfirmation, WithPagination;

    // --- FILTER PROPERTIES ---
    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterUnit = '';

    #[Url(history: true)]
    public string $filterCategory = '';

    #[Url(history: true)]
    public string $sortBy = 'latest';

    // --- MODAL STATES ---
    public bool $modalOpen = false;

    public bool $deleteModalOpen = false;

    public ?int $editingId = null;

    public ?int $toDeleteId = null;

    // --- FORM DATA ---
    public string $nama = '';

    public ?int $unit_id = null;

    public ?int $category_id = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255|unique:items,nama,'.$this->editingId,
            'unit_id' => 'nullable|exists:units,id',
            'category_id' => 'nullable|exists:categories,id',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nama' => __('Nama'),
            'unit_id' => __('Satuan'),
            'category_id' => __('Kategori'),
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Item::class);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterUnit', 'filterCategory', 'sortBy']);
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filterUnit', 'filterCategory', 'sortBy'])) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorize('create', Item::class);
        $this->reset(['nama', 'unit_id', 'category_id', 'editingId']);
        $this->modalOpen = true;
    }

    public function edit(Item $item): void
    {
        $this->authorize('update', $item);
        $this->editingId = $item->id;
        $this->nama = $item->nama;
        $this->unit_id = $item->unit_id;
        $this->category_id = $item->category_id;
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = new ItemData(
            id: $this->editingId,
            nama: $this->nama,
            unitId: $this->unit_id,
            categoryId: $this->category_id,
        );

        if ($this->editingId) {
            $this->authorize('update', Item::class);
            $item = Item::findOrFail($this->editingId);
            app(UpdateItem::class)->execute($item, $data);
            $this->success(__('Barang berhasil diperbarui.'));
        } else {
            $this->authorize('create', Item::class);
            app(StoreItem::class)->execute($data);
            $this->success(__('Barang berhasil ditambahkan.'));
        }

        $this->modalOpen = false;
    }

    public function confirmDelete(int $id): void
    {
        $this->toDeleteId = $id;
        $this->deleteModalOpen = true;
    }

    public function delete(): void
    {
        if ($this->toDeleteId) {
            $item = Item::findOrFail($this->toDeleteId);
            $this->authorize('delete', $item);

            if ($item->purchaseItems()->exists()) {
                $this->error(__('Barang memiliki transaksi, tidak bisa dihapus.'));
                $this->deleteModalOpen = false;

                return;
            }

            $item->delete();
            $this->success(__('Barang berhasil dihapus.'));
        }

        $this->deleteModalOpen = false;
    }

    public function render(): View
    {
        $query = Item::query()->with(['unit', 'category', 'supplierTerakhir']);

        if ($this->search) {
            $query->where('nama', 'like', '%'.$this->search.'%');
        }

        if ($this->filterUnit) {
            $query->where('unit_id', $this->filterUnit);
        }

        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }

        match ($this->sortBy) {
            'oldest' => $query->oldest(),
            'name_asc' => $query->orderBy('nama', 'asc'),
            'name_desc' => $query->orderBy('nama', 'desc'),
            default => $query->latest(),
        };

        $units = Unit::query()->orderBy('nama')->get()
            ->map(fn (Unit $u): array => ['id' => $u->id, 'name' => $u->nama])
            ->all();

        $categories = Category::query()->orderBy('nama')->get()
            ->map(fn (Category $c): array => ['id' => $c->id, 'name' => $c->nama])
            ->all();

        return view('livewire.admin.item.index', [
            'items' => $query->paginate(10),
            'units' => $units,
            'categories' => $categories,
        ]);
    }
}
