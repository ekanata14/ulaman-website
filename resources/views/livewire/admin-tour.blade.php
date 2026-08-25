<div data-tour="help-button">
    <x-dropdown no-x-anchor right>
        <x-slot:trigger>
            <x-button icon="o-question-mark-circle" class="btn-ghost btn-circle btn-sm"
                tooltip="{{ __('Help / Tutorial') }}" />
        </x-slot:trigger>

        {{-- Jalankan tour untuk halaman yang sedang dibuka (murni sisi klien). --}}
        <x-menu-item title="{{ __('Start page tutorial') }}" icon="o-play-circle"
            x-on:click="window.dispatchEvent(new CustomEvent('admin-tour:start'))" />

        {{-- Reset penanda onboarding + mulai ulang (agar tampil lagi saat login). --}}
        <x-menu-item title="{{ __('Reset onboarding') }}" icon="o-arrow-path" wire:click="resetTour" />
    </x-dropdown>
</div>
