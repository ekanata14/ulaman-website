<?php

namespace App\Actions\Photo;

use App\Models\Purchase;
use App\Models\PurchasePhoto;
use App\Models\User;
use App\Support\Uploads;
use Illuminate\Http\UploadedFile;

/**
 * §11 — Simpan satu foto nota ke disk privat & buat baris PurchasePhoto.
 * Validasi invariant (maks 5 foto, MIME sniffing, ukuran ≤ batas
 * App\Support\Uploads) dilakukan di sini, bukan hanya di komponen.
 * Thumbnail dibuat via job antrean.
 */
class StoreNotaPhoto
{
    public function execute(Purchase $purchase, UploadedFile $file, User $actor): PurchasePhoto
    {
        if ($purchase->photos()->count() >= 5) {
            throw new \RuntimeException('Maksimal 5 foto per nota.');
        }

        $mime = $file->getMimeType();
        if (! in_array($mime, Uploads::imageMimes(), true)) {
            throw new \RuntimeException('Tipe berkas tidak didukung (hanya JPG, PNG, WEBP).');
        }

        if ($file->getSize() > Uploads::maxBytes()) {
            throw new \RuntimeException(Uploads::tooLargeMessage());
        }

        $path = $file->store("nota-photos/{$purchase->id}", config('filesystems.default'));

        $urutan = (int) $purchase->photos()->max('urutan') + 1;

        $photo = new PurchasePhoto;
        $photo->forceFill([
            'purchase_id' => $purchase->getKey(),
            'jenis' => 'nota',
            'path' => $path,
            'nama_file_asli' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'ukuran' => $file->getSize(),
            'urutan' => $urutan,
            'uploaded_by' => $actor->getKey(),
        ]);
        $photo->save();

        GeneratePhotoThumbnail::dispatch($photo);

        return $photo;
    }
}
