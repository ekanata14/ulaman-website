<?php

namespace App\Concerns;

/**
 * Konfirmasi berbasis modal styled (menggantikan window.confirm native).
 * Komponen memakai trait ini, memanggil askConfirm(...) dari wrapper konfirmasi,
 * lalu merender satu <x-modal-confirm ... method="confirmProceed"> terikat ke state
 * generik di bawah. Otorisasi tetap dilakukan di dalam metode target.
 */
trait WithConfirmation
{
    public bool $confirmModalOpen = false;

    public string $confirmTitle = '';

    public string $confirmMessage = '';

    public string $confirmButton = '';

    public string $confirmIcon = 'o-exclamation-triangle';

    public bool $confirmDanger = true;

    public ?string $confirmMethod = null;

    /** @var array<int, mixed> */
    public array $confirmArgs = [];

    /**
     * Buka modal konfirmasi; simpan metode + argumen yang akan dijalankan saat disetujui.
     *
     * @param  array<int, mixed>  $args
     */
    public function askConfirm(
        string $method,
        array $args = [],
        string $title = '',
        string $message = '',
        bool $danger = true,
        string $icon = 'o-exclamation-triangle',
        string $button = '',
    ): void {
        $this->confirmMethod = $method;
        $this->confirmArgs = $args;
        $this->confirmTitle = $title !== '' ? $title : __('Confirmation');
        $this->confirmMessage = $message !== '' ? $message : __('Are you sure?');
        $this->confirmButton = $button !== '' ? $button : __('Yes, Continue');
        $this->confirmDanger = $danger;
        $this->confirmIcon = $icon;
        $this->confirmModalOpen = true;
    }

    /**
     * Jalankan metode yang tertunda setelah user menyetujui konfirmasi.
     */
    public function confirmProceed(): void
    {
        $method = $this->confirmMethod;
        $args = $this->confirmArgs;

        $this->confirmModalOpen = false;
        $this->confirmMethod = null;
        $this->confirmArgs = [];

        if ($method !== null && $method !== 'confirmProceed' && method_exists($this, $method)) {
            $this->{$method}(...$args);
        }
    }

    /**
     * Konfirmasi untuk aksi simpan form utama (validasi dulu agar error tampil inline).
     */
    public function confirmSave(): void
    {
        $this->validateForSave();
        $this->askConfirm(
            'save',
            [],
            __('Save this data?'),
            __('The data will be saved.'),
            false,
            'o-check-circle',
            __('Yes, Save'),
        );
    }

    /**
     * Hook validasi sebelum konfirmasi simpan; override bila komponen validasi via Form object.
     */
    protected function validateForSave(): void
    {
        $this->validate();
    }
}
