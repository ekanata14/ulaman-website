<?php

namespace App\Actions\Photo;

use App\Models\PurchasePhoto;
use Illuminate\Support\Facades\Storage;

/**
 * §11 — Hapus foto nota beserta berkas asli & thumbnail dari disk privat.
 */
class DeleteNotaPhoto
{
    public function execute(PurchasePhoto $photo): void
    {
        $disk = Storage::disk(config('filesystems.default'));

        foreach ([$photo->path, $photo->thumbnail_path] as $path) {
            if ($path && $disk->exists($path)) {
                $disk->delete($path);
            }
        }

        $photo->delete();
    }
}
