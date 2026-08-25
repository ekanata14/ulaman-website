<?php

namespace App\Livewire\Admin\Unit;

use App\Actions\Unit\StoreUnit;
use App\Concerns\WithConfirmation;
use App\DTOs\Unit\UnitData;
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
    public string $sortBy = 'latest';

    // --- MODAL STATES ---
    public bool $modalOpen = false;

    public bool $deleteModalOpen = false;

    public ?int $editingId = null;

    public ?int $toDeleteId = null;

    // --- FORM DATA ---
    public string $nama = '';

    public ?string $simbol = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'simbol' => 'nullable|string|max:50',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nama' => __('Nama'),
            'simbol' => __('Simbol'),
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Unit::class);
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
        $this->authorize('create', Unit::class);
        $this->reset(['nama', 'simbol', 'editingId']);
        $this->modalOpen = true;
    }

    public function edit(Unit $unit): void
    {
        $this->authorize('update', $unit);
        $this->editingId = $unit->id;
        $this->nama = $unit->nama;
        $this->simbol = $unit->simbol;
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $this->authorize('update', Unit::class);
            $unit = Unit::findOrFail($this->editingId);
            $unit->update([
                'nama' => $this->nama,
                'simbol' => $this->simbol,
            ]);
            $this->success(__('Satuan berhasil diperbarui.'));
        } else {
            $this->authorize('create', Unit::class);
            app(StoreUnit::class)->execute(new UnitData(
                id: null,
                nama: $this->nama,
                simbol: $this->simbol,
            ));
            $this->success(__('Satuan berhasil ditambahkan.'));
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
            $unit = Unit::findOrFail($this->toDeleteId);
            $this->authorize('delete', $unit);

            if ($unit->items()->exists()) {
                $this->error(__('Satuan masih dipakai oleh barang, tidak bisa dihapus.'));
                $this->deleteModalOpen = false;

                return;
            }

            $unit->delete();
            $this->success(__('Satuan berhasil dihapus.'));
        }

        $this->deleteModalOpen = false;
    }

    public function render(): View
    {
        $query = Unit::query()->withCount('items');

        if ($this->search) {
            $query->where(function ($q): void {
                $q->where('nama', 'like', '%'.$this->search.'%')
                    ->orWhere('simbol', 'like', '%'.$this->search.'%');
            });
        }

        match ($this->sortBy) {
            'oldest' => $query->oldest(),
            'name_asc' => $query->orderBy('nama', 'asc'),
            'name_desc' => $query->orderBy('nama', 'desc'),
            default => $query->latest(),
        };

        return view('livewire.admin.unit.index', [
            'units' => $query->paginate(10),
        ]);
    }
}
