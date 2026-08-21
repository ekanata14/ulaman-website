<?php

namespace App\Livewire\Admin\Supplier;

use App\Actions\Supplier\DetectSimilarSupplier;
use App\Actions\Supplier\StoreSupplier;
use App\Actions\Supplier\UpdateSupplier;
use App\DTOs\Supplier\SupplierData;
use App\Models\Supplier;
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
    use AuthorizesRequests, Toast, WithPagination;

    // --- FILTER PROPERTIES ---
    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterStatus = '';

    #[Url(history: true)]
    public string $sortBy = 'latest';

    // --- MODAL STATES ---
    public bool $modalOpen = false;

    public bool $deleteModalOpen = false;

    public ?int $editingId = null;

    public ?int $toDeleteId = null;

    // --- SIMILARITY CONFIRMATION ---
    public bool $confirmSimilar = false;

    // --- FORM DATA ---
    public string $nama = '';

    public ?string $alamat = null;

    public ?string $telepon = null;

    public ?string $pic = null;

    public ?string $catatan = null;

    public bool $is_active = true;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255|unique:suppliers,nama,'.$this->editingId,
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string',
            'pic' => 'nullable|string',
            'catatan' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nama' => __('Nama'),
            'alamat' => __('Alamat'),
            'telepon' => __('Telepon'),
            'pic' => __('PIC'),
            'catatan' => __('Catatan'),
            'is_active' => __('Status Aktif'),
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Supplier::class);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'sortBy']);
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filterStatus', 'sortBy'])) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorize('create', Supplier::class);
        $this->reset(['nama', 'alamat', 'telepon', 'pic', 'catatan', 'is_active', 'editingId', 'confirmSimilar']);
        $this->is_active = true;
        $this->modalOpen = true;
    }

    public function edit(Supplier $supplier): void
    {
        $this->authorize('update', $supplier);
        $this->editingId = $supplier->id;
        $this->nama = $supplier->nama;
        $this->alamat = $supplier->alamat;
        $this->telepon = $supplier->telepon;
        $this->pic = $supplier->pic;
        $this->catatan = $supplier->catatan;
        $this->is_active = $supplier->is_active;
        $this->confirmSimilar = false;
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = new SupplierData(
            id: $this->editingId,
            nama: $this->nama,
            alamat: $this->alamat,
            telepon: $this->telepon,
            pic: $this->pic,
            catatan: $this->catatan,
            isActive: $this->is_active,
        );

        if ($this->editingId) {
            $this->authorize('update', Supplier::class);
            $supplier = Supplier::findOrFail($this->editingId);
            app(UpdateSupplier::class)->execute($supplier, $data);
            $this->success(__('Supplier berhasil diperbarui.'));
        } else {
            $this->authorize('create', Supplier::class);

            if (! $this->confirmSimilar) {
                $similar = app(DetectSimilarSupplier::class)->execute($this->nama);

                if ($similar->isNotEmpty()) {
                    $names = $similar->pluck('nama')->implode(', ');
                    $this->confirmSimilar = true;
                    $this->warning(__('Mirip dengan supplier: :names. Simpan lagi untuk tetap lanjut.', ['names' => $names]));

                    return;
                }
            }

            app(StoreSupplier::class)->execute($data);
            $this->success(__('Supplier berhasil ditambahkan.'));
        }

        $this->confirmSimilar = false;
        $this->modalOpen = false;
    }

    public function toggleActive(Supplier $supplier): void
    {
        $this->authorize('update', $supplier);

        $data = new SupplierData(
            id: $supplier->id,
            nama: $supplier->nama,
            alamat: $supplier->alamat,
            telepon: $supplier->telepon,
            pic: $supplier->pic,
            catatan: $supplier->catatan,
            isActive: ! $supplier->is_active,
        );

        app(UpdateSupplier::class)->execute($supplier, $data);
        $this->success(__('Status supplier diperbarui.'));
    }

    public function confirmDelete(int $id): void
    {
        $this->toDeleteId = $id;
        $this->deleteModalOpen = true;
    }

    public function delete(): void
    {
        if ($this->toDeleteId) {
            $supplier = Supplier::findOrFail($this->toDeleteId);
            $this->authorize('delete', $supplier);

            if ($supplier->purchases()->exists()) {
                $this->error(__('Supplier memiliki transaksi, tidak bisa dihapus. Nonaktifkan saja.'));
                $this->deleteModalOpen = false;

                return;
            }

            $supplier->delete();
            $this->success(__('Supplier berhasil dihapus.'));
        }

        $this->deleteModalOpen = false;
    }

    public function render(): View
    {
        $query = Supplier::query()->withCount('purchases');

        if ($this->search) {
            $query->where(function ($q): void {
                $q->where('nama', 'like', '%'.$this->search.'%')
                    ->orWhere('pic', 'like', '%'.$this->search.'%')
                    ->orWhere('telepon', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterStatus === 'active') {
            $query->where('is_active', true);
        } elseif ($this->filterStatus === 'inactive') {
            $query->where('is_active', false);
        }

        match ($this->sortBy) {
            'oldest' => $query->oldest(),
            'name_asc' => $query->orderBy('nama', 'asc'),
            'name_desc' => $query->orderBy('nama', 'desc'),
            default => $query->latest(),
        };

        return view('livewire.admin.supplier.index', [
            'suppliers' => $query->paginate(10),
        ]);
    }
}
