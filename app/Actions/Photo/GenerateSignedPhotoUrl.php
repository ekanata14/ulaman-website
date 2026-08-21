<?php

namespace App\Actions\Photo;

use App\Models\PurchasePhoto;
use Illuminate\Support\Facades\URL;

/**
 * §11 — Buat URL bertanda-tangan (berlaku 15 menit) untuk streaming foto/thumb.
 */
class GenerateSignedPhotoUrl
{
    public function execute(PurchasePhoto $photo, bool $thumb = false): string
    {
        return URL::temporarySignedRoute('nota.photo', now()->addMinutes(15), [
            'photo' => $photo->getKey(),
            'thumb' => $thumb ? 1 : 0,
        ]);
    }
}
