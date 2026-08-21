<?php

namespace App\Actions\Purchase;

use App\Actions\Concerns\InteractsWithAuditLog;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * §10.2 — Soft delete nota; cascade hard-delete item, bundle, foto (§AC-03.4)
 * beserta berkas fisik foto.
 */
class DeletePurchase
{
    use InteractsWithAuditLog;

    public function execute(Purchase $purchase, User $actor): void
    {
        DB::transaction(function () use ($purchase, $actor): void {
            $purchase->loadMissing('photos');

            $disk = Storage::disk(config('filesystems.default'));
            foreach ($purchase->photos as $photo) {
                foreach ([$photo->path, $photo->thumbnail_path] as $path) {
                    if ($path !== null && $path !== '' && $disk->exists($path)) {
                        $disk->delete($path);
                    }
                }
            }

            $this->recordAudit($actor, 'delete', $purchase, ['grand_total' => (string) $purchase->grand_total], null);

            $purchase->photos()->delete();
            $purchase->bundles()->delete();
            $purchase->items()->delete();
            $purchase->delete();
        });
    }
}
