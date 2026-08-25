@props([
    'title' => 'Konfirmasi',
    'text' => 'Apakah Anda yakin?',
    'icon' => 'o-exclamation-triangle',
    'confirmText' => 'Ya, Hapus',
    'cancelText' => 'Batal',
    'method' => 'delete', // Method di Livewire yang akan dipanggil
    'loading' => true, // Tampilkan spinner saat proses
    'danger' => true, // false = varian non-destruktif (biru/primary)
])

{{-- Wrapper Modal (Backdrop Blur) --}}
<x-modal {{ $attributes }} class="backdrop-blur-sm">

    <div class="mb-5 text-center">
        {{-- Icon Peringatan Besar --}}
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4 {{ $danger ? 'bg-error/10' : 'bg-info/10' }}">
            <x-icon name="{{ $icon }}" class="w-8 h-8 {{ $danger ? 'text-error' : 'text-info' }}" />
        </div>

        {{-- Judul --}}
        <h3 class="text-lg font-bold text-base-content">
            {{ $title }}
        </h3>

        {{-- Deskripsi --}}
        <div class="mt-2">
            <p class="text-sm text-gray-500">
                {{ $text }}
            </p>
        </div>
    </div>

    {{-- Tombol Aksi (Grid Layout) --}}
    <div class="grid grid-cols-2 gap-3 mt-6">
        {{-- Tombol Batal --}}
        <x-button label="{{ $cancelText }}" class="btn-ghost border border-base-300 bg-base-100"
            @click="$wire.{{ $attributes->wire('model')->value() }} = false" />

        {{-- Tombol Konfirmasi --}}
        <x-button label="{{ $confirmText }}" class="{{ $danger ? 'btn-error' : 'btn-primary' }} text-white"
            wire:click="{{ $method }}" spinner />
    </div>
</x-modal>
