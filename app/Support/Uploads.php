<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Kebijakan unggahan berkas (foto nota & bukti transfer).
 *
 * Ukuran dan tipe didefinisikan sekali di config/ulaman.php, lalu dipakai
 * bersama oleh aturan validasi Livewire, penjaga di Action penyimpan, dan
 * pratinjau di Blade — supaya tidak ada lagi angka batas yang tersebar.
 */
final class Uploads
{
    /** @return list<string> MIME gambar yang didukung (JPG/PNG/WEBP). */
    public static function imageMimes(): array
    {
        return config('ulaman.upload.image_mimes');
    }

    /** @return list<string> MIME bukti transfer (gambar + PDF). */
    public static function buktiMimes(): array
    {
        return config('ulaman.upload.bukti_mimes');
    }

    /** Batas ukuran dalam kilobyte — untuk aturan `max:` Livewire. */
    public static function maxKb(): int
    {
        return (int) config('ulaman.upload.max_kb');
    }

    /** Batas ukuran dalam byte — untuk penjaga di Action. */
    public static function maxBytes(): int
    {
        return self::maxKb() * 1024;
    }

    /** Batas ukuran dalam MB — untuk pesan & petunjuk di UI. */
    public static function maxMb(): int
    {
        return intdiv(self::maxKb(), 1024);
    }

    /** Pesan seragam ketika berkas melebihi batas. */
    public static function tooLargeMessage(): string
    {
        return 'Ukuran berkas melebihi '.self::maxMb().' MB.';
    }

    /**
     * Layak dipratinjau sebagai gambar di browser?
     *
     * Hanya tipe yang benar-benar didukung yang lolos. HEIC/HEIF (format
     * bawaan kamera iPhone) sengaja ditolak: browser non-Safari tidak bisa
     * merendernya, dan Livewire melempar FileNotPreviewableException ketika
     * ekstensi tidak ada di livewire.temporary_file_upload.preview_mimes.
     */
    public static function isPreviewableImage(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), self::imageMimes(), true);
    }
}
