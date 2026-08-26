<?php

namespace App\Actions\Photo;

use App\Models\Purchase;
use App\Models\PurchasePhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * §11 — Simpan satu bukti transfer/pembayaran ke disk privat & buat baris
 * PurchasePhoto (jenis 'bukti_transfer'). Menerima gambar (JPG/PNG/WEBP) & PDF.
 * Thumbnail hanya dibuat untuk gambar; PDF disajikan apa adanya via signed URL.
 */
class StoreBuktiTransfer
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public function execute(Purchase $purchase, UploadedFile $file, User $actor): PurchasePhoto
    {
        if ($purchase->buktiTransfers()->count() >= 5) {
            throw new \RuntimeException('Maksimal 5 bukti transfer per nota.');
        }

        $mime = $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \RuntimeException('Tipe berkas tidak didukung (hanya JPG, PNG, WEBP, PDF).');
        }

        if ($file->getSize() > 50 * 1024 * 1024) {
            throw new \RuntimeException('Ukuran berkas melebihi 50 MB.');
        }

        $path = $file->store("bukti-transfer/{$purchase->id}", config('filesystems.default'));

        $urutan = (int) $purchase->buktiTransfers()->max('urutan') + 1;

        $photo = new PurchasePhoto;
        $photo->forceFill([
            'purchase_id' => $purchase->getKey(),
            'jenis' => 'bukti_transfer',
            'path' => $path,
            'nama_file_asli' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'ukuran' => $file->getSize(),
            'urutan' => $urutan,
            'uploaded_by' => $actor->getKey(),
        ]);
        $photo->save();

        if (in_array($mime, self::IMAGE_MIMES, true)) {
            GeneratePhotoThumbnail::dispatch($photo);
        }

        return $photo;
    }
}
