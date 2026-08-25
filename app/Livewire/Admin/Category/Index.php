<?php

namespace App\Livewire\Admin\Category;

use App\Actions\Category\StoreCategory;
use App\Concerns\WithConfirmation;
use App\DTOs\Category\CategoryData;
use App\Models\Category;
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
    public string $sortBy = 'latest';

    // --- MODAL STATES ---
    public bool $modalOpen = false;

    public bool $deleteModalOpen = false;

    public ?int $editingId = null;

    public ?int $toDeleteId = null;

    // --- FORM DATA ---
    public string $nama = '';

    public ?string $warna = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'warna' => 'nullable|string|max:50',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nama' => __('Nama'),
            'warna' => __('Warna'),
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'sortBy']);
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'sortBy'])) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorize('create', Category::class);
        $this->reset(['nama', 'warna', 'editingId']);
        $this->modalOpen = true;
    }

    public function edit(Category $category): void
    {
        $this->authorize('update', $category);
        $this->editingId = $category->id;
        $this->nama = $category->nama;
        $this->warna = $category->warna;
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $this->authorize('update', Category::class);
            $category = Category::findOrFail($this->editingId);
            $category->update([
                'nama' => $this->nama,
                'warna' => $this->warna,
            ]);
            $this->success(__('Kategori berhasil diperbarui.'));
        } else {
            $this->authorize('create', Category::class);
            app(StoreCategory::class)->execute(new CategoryData(
                id: null,
                nama: $this->nama,
                warna: $this->warna,
            ));
            $this->success(__('Kategori berhasil ditambahkan.'));
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
            $category = Category::findOrFail($this->toDeleteId);
            $this->authorize('delete', $category);

            if ($category->items()->exists() || $category->purchases()->exists()) {
                $this->error(__('Kategori masih dipakai, tidak bisa dihapus.'));
                $this->deleteModalOpen = false;

                return;
            }

            $category->delete();
            $this->success(__('Kategori berhasil dihapus.'));
        }

        $this->deleteModalOpen = false;
    }

    public function render(): View
    {
        $query = Category::query()->withCount(['items', 'purchases']);

        if ($this->search) {
            $query->where('nama', 'like', '%'.$this->search.'%');
        }

        match ($this->sortBy) {
            'oldest' => $query->oldest(),
            'name_asc' => $query->orderBy('nama', 'asc'),
            'name_desc' => $query->orderBy('nama', 'desc'),
            default => $query->latest(),
        };

        return view('livewire.admin.category.index', [
            'categories' => $query->paginate(10),
        ]);
    }
}
