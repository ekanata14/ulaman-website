<?php

namespace App\Actions\Photo;

use App\Models\PurchasePhoto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * §11 — Job antrean pembuat thumbnail (lebar maks 300px, JPG q80).
 * Disk privat sama dengan foto asli. Idempoten: melewati jika berkas hilang.
 */
class GeneratePhotoThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public PurchasePhoto $photo) {}

    public function handle(): void
    {
        $disk = Storage::disk(config('filesystems.default'));

        if (! $disk->exists($this->photo->path)) {
            return;
        }

        $img = (new ImageManager(new Driver))->decode($disk->get($this->photo->path));
        $img->scaleDown(width: 300);

        $thumbPath = 'nota-photos/'.$this->photo->purchase_id.'/thumb_'.basename($this->photo->path).'.jpg';
        $disk->put($thumbPath, (string) $img->encodeUsingFileExtension('jpg', quality: 80));

        $this->photo->forceFill(['thumbnail_path' => $thumbPath])->save();
    }
}
