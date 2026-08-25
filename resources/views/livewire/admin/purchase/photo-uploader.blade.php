<div>
<x-card class="bg-base-100 shadow-sm mb-6">
    <x-slot:title><span class="font-bold text-lg">{{ __('Nota Photos') }}</span></x-slot:title>

    {{-- UPLOAD (kompresi klien + kamera langsung di mobile) --}}
    <div x-data="photoUpload">
        <input type="file" x-ref="input" accept="image/*" capture="environment" multiple
            class="file-input file-input-bordered w-full" @change="handle($event)" :disabled="uploading" />
        <div class="text-xs text-gray-400 mt-1">
            {{ __('Max 5 photos, 10 MB each. Compressed on your device before upload.') }}
        </div>
        <div x-show="uploading" class="mt-2">
            <progress class="progress progress-primary w-full" :value="progress" max="100"></progress>
            <span class="text-xs" x-text="`${progress}%`"></span>
        </div>
    </div>

    <div wire:loading wire:target="storeUploaded" class="text-sm text-gray-500 mt-2">
        <x-loading class="loading-sm" /> {{ __('Saving...') }}
    </div>

    {{-- GALERI --}}
    @if (count($photos) > 0)
        <div class="grid grid-cols-3 md:grid-cols-5 gap-3 mt-4"
            x-data="{ urls: @js(collect($photos)->pluck('full')->values()) }">
            @foreach ($photos as $idx => $photo)
                <div class="relative group" wire:key="photo-{{ $photo['id'] }}">
                    <img src="{{ $photo['thumb'] }}" alt="{{ $photo['nama'] }}"
                        class="w-full h-28 object-cover rounded-lg border border-base-300 cursor-pointer"
                        @click="$dispatch('open-photo-lightbox', { images: urls, index: {{ $idx }} })" />
                    <x-button icon="o-trash" wire:click="confirmDeletePhoto({{ $photo['id'] }})"
                        class="btn-xs btn-circle btn-error absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition" />
                </div>
            @endforeach
        </div>
    @else
        <div class="text-sm text-gray-400 mt-4">{{ __('No photos yet.') }}</div>
    @endif
</x-card>

<x-modal-confirm wire:model="confirmModalOpen" :title="$confirmTitle" :text="$confirmMessage"
    :confirm-text="$confirmButton" :icon="$confirmIcon" :danger="$confirmDanger" method="confirmProceed" />
</div>
