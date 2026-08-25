<?php

namespace App\Livewire\Guest;

use App\Actions\Report\GetPurchaseItemList;
use App\Actions\Report\GetPurchaseList;
use App\Actions\Report\GetPurchaseNotasForSpreadsheet;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Models\Category;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * §11.2 / §F-06 — Halaman publik guest. Read-only: TIDAK ADA method mutasi data
 * (AC-06.4). Hanya nota final yang tampil; created_by tidak pernah ditampilkan.
 * Seluruh filter #[Url(history:true)] agar URL bisa dibagikan (AC-06.3).
 */
#[Layout('layouts.guest')]
class PurchaseBrowser extends Component
{
    use WithPagination;

    #[Url(as: 'dari', history: true)]
    public ?string $startDate = null;

    #[Url(as: 'sampai', history: true)]
    public ?string $endDate = null;

    /** @var array<int, int> */
    #[Url(as: 'supplier', history: true)]
    public array $supplierIds = [];

    /** @var array<int, int> */
    #[Url(as: 'kategori', history: true)]
    public array $categoryIds = [];

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'mode', history: true)]
    public string $viewMode = 'nota';

    #[Url(as: 'foto', history: true)]
    public bool $onlyWithPhoto = false;

    #[Url(history: true)]
    public string $sort = 'tanggal';

    #[Url(history: true)]
    public string $order = 'desc';

    #[Url(history: true)]
    public int $perPage = 50;

    /** State presentasi murni: buka/tutup modal filter (bukan mutasi data). */
    public bool $showFilters = false;

    /**
     * Jumlah filter aktif — untuk badge tombol Filter. Bukan mutasi data.
     */
    public function activeFilterCount(): int
    {
        return collect([
            filled($this->startDate),
            filled($this->endDate),
            count($this->supplierIds) > 0,
            count($this->categoryIds) > 0,
            filled($this->search),
            $this->onlyWithPhoto,
        ])->filter()->count();
    }

    public function updated(string $property): void
    {
        if (in_array($property, [
            'startDate', 'endDate', 'supplierIds', 'categoryIds',
            'search', 'viewMode', 'onlyWithPhoto', 'sort', 'order', 'perPage',
        ], true)) {
            $this->resetPage();
        }
    }

    /**
     * Terapkan filter dari hasil pencarian global (event dari Guest\GlobalSearch).
     * Hanya menyetel state filter/presentasi — bukan mutasi data.
     */
    #[On('guest-filter-supplier')]
    public function applySupplier(int $id): void
    {
        $this->supplierIds = [$id];
        $this->viewMode = 'nota';
        $this->resetPage();
    }

    #[On('guest-filter-category')]
    public function applyCategory(int $id): void
    {
        $this->categoryIds = [$id];
        $this->viewMode = 'nota';
        $this->resetPage();
    }

    #[On('guest-search-items')]
    public function applyItemSearch(string $term): void
    {
        $this->search = $term;
        $this->viewMode = 'item';
        $this->resetPage();
    }

    public function applyPreset(string $preset): void
    {
        $now = CarbonImmutable::now();

        [$this->startDate, $this->endDate] = match ($preset) {
            'this_month' => [$now->startOfMonth()->toDateString(), $now->endOfMonth()->toDateString()],
            'last_month' => [$now->subMonth()->startOfMonth()->toDateString(), $now->subMonth()->endOfMonth()->toDateString()],
            'last_3' => [$now->subMonths(2)->startOfMonth()->toDateString(), $now->endOfMonth()->toDateString()],
            default => [null, null],
        };

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['startDate', 'endDate', 'supplierIds', 'categoryIds', 'search', 'onlyWithPhoto']);
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    private function filterArray(): array
    {
        return [
            'dari' => $this->startDate,
            'sampai' => $this->endDate,
            'supplierIds' => array_map('intval', $this->supplierIds),
            'categoryIds' => array_map('intval', $this->categoryIds),
            'search' => $this->search,
            'onlyWithPhoto' => $this->onlyWithPhoto,
        ];
    }

    private function filter(): PurchaseFilterData
    {
        return new PurchaseFilterData(
            dari: $this->startDate,
            sampai: $this->endDate,
            supplierIds: array_map('intval', $this->supplierIds),
            categoryIds: array_map('intval', $this->categoryIds),
            search: $this->search,
            viewMode: $this->viewMode,
            onlyWithPhoto: $this->onlyWithPhoto,
            sort: $this->sort,
            order: $this->order,
            perPage: $this->perPage,
        );
    }

    public function render(): View
    {
        $filter = $this->filter();

        $rows = match ($this->viewMode) {
            'item' => app(GetPurchaseItemList::class)->execute($filter)->paginate($this->perPage),
            'spreadsheet' => app(GetPurchaseNotasForSpreadsheet::class)->execute($filter, onlyFinal: true)->paginate($this->perPage),
            default => app(GetPurchaseList::class)->execute($filter)->paginate($this->perPage),
        };

        return view('livewire.guest.purchase-browser', [
            'rows' => $rows,
            'filterArray' => $this->filterArray(),
            'suppliers' => Supplier::query()->orderBy('nama')->get()
                ->map(fn (Supplier $s): array => ['id' => $s->id, 'name' => $s->nama])->all(),
            'categories' => Category::query()->orderBy('nama')->get()
                ->map(fn (Category $c): array => ['id' => $c->id, 'name' => $c->nama])->all(),
        ]);
    }
}
