<?php

use App\Actions\Photo\GeneratePhotoThumbnail;
use App\Actions\Photo\StoreBuktiTransfer;
use App\Actions\Photo\StoreNotaPhoto;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

function btPurchase(): Purchase
{
    $purchase = new Purchase;
    $purchase->forceFill([
        'kode' => 'PB-BT-'.fake()->unique()->numberBetween(1000, 9999),
        'tanggal' => now(),
        'status' => 'final',
        'grand_total' => 0,
        'subtotal' => 0,
        'total_diskon_item' => 0,
        'total_diskon_bundle' => 0,
        'diskon_nota_nilai' => 0,
        'diskon_nota_amount' => 0,
    ])->save();

    return $purchase;
}

it('menyimpan bukti transfer gambar (jenis bukti_transfer + antre thumbnail)', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = btPurchase();

    $photo = app(StoreBuktiTransfer::class)->execute(
        $purchase,
        UploadedFile::fake()->image('transfer.jpg', 800, 600),
        $user,
    );

    expect($photo->jenis)->toBe('bukti_transfer')
        ->and($photo->mime_type)->toBe('image/jpeg');

    Storage::disk(config('filesystems.default'))->assertExists($photo->path);
    Queue::assertPushed(GeneratePhotoThumbnail::class);

    // Relasi terpisah dari foto nota.
    expect($purchase->buktiTransfers()->count())->toBe(1)
        ->and($purchase->photos()->count())->toBe(0);
});

it('menyimpan bukti transfer PDF tanpa mengantre thumbnail', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = btPurchase();

    $photo = app(StoreBuktiTransfer::class)->execute(
        $purchase,
        UploadedFile::fake()->create('struk.pdf', 200, 'application/pdf'),
        $user,
    );

    expect($photo->jenis)->toBe('bukti_transfer')
        ->and($photo->mime_type)->toBe('application/pdf');

    Storage::disk(config('filesystems.default'))->assertExists($photo->path);
    Queue::assertNotPushed(GeneratePhotoThumbnail::class);
});

it('menolak bukti transfer melebihi 50 MB', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = btPurchase();

    expect(fn () => app(StoreBuktiTransfer::class)->execute(
        $purchase,
        UploadedFile::fake()->create('big.pdf', 51201, 'application/pdf'),
        $user,
    ))->toThrow(RuntimeException::class, 'Ukuran berkas melebihi 50 MB.');
});

it('menolak tipe berkas bukti transfer yang tidak didukung', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = btPurchase();

    expect(fn () => app(StoreBuktiTransfer::class)->execute(
        $purchase,
        UploadedFile::fake()->create('data.zip', 100, 'application/zip'),
        $user,
    ))->toThrow(RuntimeException::class);
});

it('menolak bukti transfer ke-6', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = btPurchase();

    for ($i = 0; $i < 5; $i++) {
        $purchase->buktiTransfers()->create([
            'jenis' => 'bukti_transfer',
            'path' => "bukti-transfer/{$purchase->id}/f{$i}.jpg",
            'nama_file_asli' => "f{$i}.jpg",
            'mime_type' => 'image/jpeg',
            'ukuran' => 100,
            'urutan' => $i + 1,
            'uploaded_by' => $user->getKey(),
        ]);
    }

    expect(fn () => app(StoreBuktiTransfer::class)->execute(
        $purchase,
        UploadedFile::fake()->image('transfer.jpg'),
        $user,
    ))->toThrow(RuntimeException::class, 'Maksimal 5 bukti transfer per nota.');
});

it('StoreNotaPhoto memberi jenis nota dan tidak tercampur bukti transfer', function () {
    Storage::fake(config('filesystems.default'));
    Queue::fake();

    $user = User::factory()->create(['role' => 'admin']);
    $purchase = btPurchase();

    $nota = app(StoreNotaPhoto::class)->execute($purchase, UploadedFile::fake()->image('nota.jpg'), $user);
    $bukti = app(StoreBuktiTransfer::class)->execute($purchase, UploadedFile::fake()->image('bt.jpg'), $user);

    expect($nota->jenis)->toBe('nota')
        ->and($bukti->jenis)->toBe('bukti_transfer')
        ->and($purchase->photos()->count())->toBe(1)
        ->and($purchase->buktiTransfers()->count())->toBe(1);
});
