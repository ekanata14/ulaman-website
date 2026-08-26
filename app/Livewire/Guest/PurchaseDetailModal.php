<?php

namespace App\Livewire\Guest;

use App\Actions\Photo\GenerateSignedPhotoUrl;
use App\Models\Purchase;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * §11.2 — Detail nota untuk guest (modal). Read-only: hanya membaca nota final
 * (draft tidak pernah tampil), tanpa method mutasi data. created_by tidak tampil.
 */
class PurchaseDetailModal extends Component
{
    public bool $modalOpen = false;

    public ?int $purchaseId = null;

    #[On('open-purchase-detail')]
    public function openDetail(int $purchaseId): void
    {
        $this->purchaseId = $purchaseId;
        $this->modalOpen = true;
    }

    public function render(): View
    {
        $purchase = null;
        $photos = [];

        if ($this->purchaseId !== null) {
            $purchase = Purchase::query()
                ->final()
                ->with(['supplier', 'items.unit', 'bundles.bundleItems.purchaseItem', 'photos'])
                ->find($this->purchaseId);

            if ($purchase !== null) {
                $signer = app(GenerateSignedPhotoUrl::class);
                $photos = $purchase->photos
                    ->map(fn ($p): array => [
                        'thumb' => $signer->execute($p, true),
                        'full' => $signer->execute($p, false),
                    ])
                    ->all();
            }
        }

        return view('livewire.guest.purchase-detail-modal', [
            'purchase' => $purchase,
            'photos' => $photos,
        ]);
    }
}
