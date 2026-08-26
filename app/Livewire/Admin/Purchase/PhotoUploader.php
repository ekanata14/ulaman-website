<?php

namespace App\Livewire\Admin\Purchase;

use App\Actions\Photo\DeleteNotaPhoto;
use App\Actions\Photo\GenerateSignedPhotoUrl;
use App\Actions\Photo\StoreNotaPhoto;
use App\Concerns\WithConfirmation;
use App\Models\Purchase;
use App\Models\PurchasePhoto;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

/**
 * §11.3 / §F-05 — Uploader Foto Nota. Kompresi klien (browser-image-compression)
 * dilakukan sebelum upload; komponen memvalidasi lalu mendelegasi ke StoreNotaPhoto
 * (MIME sniffing + thumbnail job di sana). Akses berkas hanya lewat signed URL.
 */
class PhotoUploader extends Component
{
    use AuthorizesRequests, Toast, WithConfirmation, WithFileUploads;

    public Purchase $purchase;

    /** @var array<int, \Illuminate\Http\UploadedFile> */
    public array $photos = [];

    public function mount(Purchase $purchase): void
    {
        $this->authorize('update', $purchase);
        $this->purchase = $purchase;
    }

    public function storeUploaded(): void
    {
        $this->authorize('update', $this->purchase);

        $this->validate([
            'photos' => ['array', 'max:5'],
            'photos.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:51200'],
        ]);

        $actor = $this->actor();
        foreach ($this->photos as $file) {
            try {
                app(StoreNotaPhoto::class)->execute($this->purchase, $file, $actor);
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
            }
        }

        $this->photos = [];
        $this->success(__('Photos uploaded.'));
    }

    public function confirmDeletePhoto(int $id): void
    {
        $this->askConfirm(
            'deletePhoto',
            [$id],
            __('Delete this photo?'),
            __('This photo will be permanently removed.'),
            true,
            'o-trash',
            __('Yes, Delete'),
        );
    }

    public function deletePhoto(int $id): void
    {
        $this->authorize('update', $this->purchase);

        $photo = $this->purchase->photos()->whereKey($id)->first();
        if ($photo !== null) {
            app(DeleteNotaPhoto::class)->execute($photo);
            $this->success(__('Photo deleted.'));
        }
    }

    public function render(): View
    {
        $signer = app(GenerateSignedPhotoUrl::class);

        $savedPhotos = $this->purchase->photos()->orderBy('urutan')->get()
            ->map(fn (PurchasePhoto $p): array => [
                'id' => $p->id,
                'thumb' => $signer->execute($p, true),
                'full' => $signer->execute($p, false),
                'nama' => $p->nama_file_asli,
            ])
            ->all();

        return view('livewire.admin.purchase.photo-uploader', ['savedPhotos' => $savedPhotos]);
    }

    private function actor(): User
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
