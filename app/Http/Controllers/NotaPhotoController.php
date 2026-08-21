<?php

namespace App\Http\Controllers;

use App\Models\PurchasePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * §11 — Streaming foto nota dari disk privat. Route dilindungi middleware
 * `signed`; tanda-tangan adalah penjaganya (tanpa auth di sini).
 */
class NotaPhotoController extends Controller
{
    public function __invoke(Request $request, PurchasePhoto $photo): StreamedResponse
    {
        $disk = Storage::disk(config('filesystems.default'));

        $thumb = $request->boolean('thumb');
        $path = ($thumb && $photo->thumbnail_path && $disk->exists($photo->thumbnail_path))
            ? $photo->thumbnail_path
            : $photo->path;

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
