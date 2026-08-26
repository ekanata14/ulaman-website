<?php

use App\Actions\Photo\DeleteNotaPhoto;
use App\Actions\Photo\GeneratePhotoThumbnail;
use App\Actions\Photo\GenerateSignedPhotoUrl;
use App\Actions\Photo\StoreNotaPhoto;
use App\Models\Purchase;
use App\Models\PurchasePhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

function makeTestPurchase(): Purchase
{
    $purchase = new Purchase;
    $purchase->forceFill([
        'kode' => 'PB-TEST-'.fake()->unique()->numberBetween(1000, 9999),
        'tanggal' => now(),
        'status' => 'draft',
        'grand_total' => 0,
        'subtotal' => 0,
        'total_diskon_item' => 0,
        'total_diskon_bundle' => 0,
        'diskon_nota_nilai' => 0,
        'diskon_nota_amount' => 0,
    ])->save();

    return $purchase;
}

it('menyimpan foto dan mengantrekan job thumbnail', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = makeTestPurchase();
    $file = UploadedFile::fake()->image('nota.jpg', 800, 600);

    $photo = app(StoreNotaPhoto::class)->execute($purchase, $file, $user);

    expect($photo->purchase_id)->toBe($purchase->getKey())
        ->and($photo->mime_type)->toBe('image/jpeg')
        ->and($photo->ukuran)->toBeGreaterThan(0)
        ->and($photo->uploaded_by)->toBe($user->getKey());

    Storage::disk(config('filesystems.default'))->assertExists($photo->path);
    Queue::assertPushed(GeneratePhotoThumbnail::class);
});

it('menolak foto ke-6', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = makeTestPurchase();

    for ($i = 0; $i < 5; $i++) {
        $purchase->photos()->create([
            'path' => "nota-photos/{$purchase->id}/f{$i}.jpg",
            'nama_file_asli' => "f{$i}.jpg",
            'mime_type' => 'image/jpeg',
            'ukuran' => 100,
            'urutan' => $i + 1,
            'uploaded_by' => $user->getKey(),
        ]);
    }

    expect(fn () => app(StoreNotaPhoto::class)->execute(
        $purchase,
        UploadedFile::fake()->image('nota.jpg', 800, 600),
        $user,
    ))->toThrow(RuntimeException::class, 'Maksimal 5 foto per nota.');
});

it('menolak berkas melebihi 50 MB', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = makeTestPurchase();
    $file = UploadedFile::fake()->create('big.jpg', 51201, 'image/jpeg'); // 51201 KB > 50 MB

    expect(fn () => app(StoreNotaPhoto::class)->execute($purchase, $file, $user))
        ->toThrow(RuntimeException::class, 'Ukuran berkas melebihi 50 MB.');
});

it('menolak berkas non-gambar', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = makeTestPurchase();
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    expect(fn () => app(StoreNotaPhoto::class)->execute($purchase, $file, $user))
        ->toThrow(RuntimeException::class);
});

it('menghapus baris DB dan berkas', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = makeTestPurchase();
    $photo = app(StoreNotaPhoto::class)->execute(
        $purchase,
        UploadedFile::fake()->image('nota.jpg', 800, 600),
        $user,
    );
    $path = $photo->path;

    app(DeleteNotaPhoto::class)->execute($photo);

    expect(PurchasePhoto::find($photo->getKey()))->toBeNull();
    Storage::disk(config('filesystems.default'))->assertMissing($path);
});

it('membuat URL bertanda-tangan berisi signature dan id foto', function () {
    Route::get('/nota/foto/{photo}', fn () => null)
        ->name('nota.photo')
        ->middleware('signed');
    Route::getRoutes()->refreshNameLookups();

    Storage::fake(config('filesystems.default'));
    Queue::fake();

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = makeTestPurchase();
    $photo = app(StoreNotaPhoto::class)->execute(
        $purchase,
        UploadedFile::fake()->image('nota.jpg', 800, 600),
        $user,
    );

    $url = app(GenerateSignedPhotoUrl::class)->execute($photo);

    expect($url)->toContain('signature=')
        ->and($url)->toContain('/nota/foto/'.$photo->getKey());
});

it('job thumbnail menghasilkan berkas dan menyimpan thumbnail_path', function () {
    Storage::fake(config('filesystems.default'));

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = makeTestPurchase();

    Queue::fake();
    $photo = app(StoreNotaPhoto::class)->execute(
        $purchase,
        UploadedFile::fake()->image('nota.jpg', 800, 600),
        $user,
    );

    (new GeneratePhotoThumbnail($photo))->handle();

    $fresh = $photo->fresh();
    expect($fresh)->not->toBeNull()
        ->and($fresh->thumbnail_path)->not->toBeNull();

    Storage::disk(config('filesystems.default'))->assertExists($fresh->thumbnail_path);
});
