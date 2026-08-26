<?php

namespace App\Livewire\Admin\Import;

use App\Actions\Export\BuildImportTemplate;
use App\Actions\Import\ImportPurchaseExcel;
use App\Actions\Import\PreviewPurchaseImport;
use App\Concerns\WithConfirmation;
use App\DTOs\Import\ImportPreviewData;
use App\Models\Purchase;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * §11.4 / §F-09 — Wizard impor 3 langkah: Upload → Preview & Peringatan →
 * Eksekusi (queued job dipantau wire:poll). Importer tidak menghitung apa pun;
 * setiap nota lewat StorePurchase.
 */
#[Layout('layouts.app')]
class Wizard extends Component
{
    use AuthorizesRequests, Toast, WithConfirmation, WithFileUploads;

    public int $step = 1;

    public mixed $file = null;

    public ?string $storedPath = null;

    public ?ImportPreviewData $preview = null;

    public bool $importing = false;

    public function mount(): void
    {
        $this->authorize('create', Purchase::class);
    }

    public function downloadTemplate(BuildImportTemplate $action): BinaryFileResponse
    {
        $this->authorize('create', Purchase::class);

        return $action->execute();
    }

    public function toPreview(): void
    {
        $this->authorize('create', Purchase::class);
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ]);

        $this->storedPath = $this->file->store('imports', config('filesystems.default'));
        $this->preview = app(PreviewPurchaseImport::class)->execute($this->absolutePath());
        $this->step = 2;
    }

    public function confirmExecute(): void
    {
        $this->askConfirm(
            'execute',
            [],
            __('Import all items into the database?'),
            __('All parsed items will be created. This runs a queued import job.'),
            false,
            'o-arrow-down-tray',
            __('Yes, Import'),
        );
    }

    public function execute(): void
    {
        $this->authorize('create', Purchase::class);
        if ($this->storedPath === null) {
            return;
        }

        $total = $this->preview instanceof ImportPreviewData ? $this->preview->totalNota : 0;
        Cache::put('import:progress', ['done' => 0, 'total' => $total, 'finished' => false], 3600);
        Cache::forget('import:reconciliation');

        ImportPurchaseExcel::dispatch($this->absolutePath(), (int) auth()->id());

        $this->importing = true;
        $this->step = 3;
    }

    /**
     * @return array<string, mixed>
     */
    public function progress(): array
    {
        return Cache::get('import:progress', ['done' => 0, 'total' => 0, 'finished' => false]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function reconciliation(): ?array
    {
        return Cache::get('import:reconciliation');
    }

    private function absolutePath(): string
    {
        return Storage::disk(config('filesystems.default'))->path((string) $this->storedPath);
    }

    public function render(): View
    {
        return view('livewire.admin.import.wizard', [
            'progress' => $this->progress(),
            'reconciliation' => $this->reconciliation(),
        ]);
    }
}
