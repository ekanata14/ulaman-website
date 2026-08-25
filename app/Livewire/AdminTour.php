<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class AdminTour extends Component
{
    /**
     * Tandai tour onboarding sudah selesai untuk user aktif.
     * Dipicu dari JS (Driver.js onDestroyed) via event browser 'tour-finished'.
     * Preferensi lain tetap dipertahankan (merge, bukan timpa).
     */
    #[On('tour-finished')]
    public function markCompleted(): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $preferences = $user->preferences ?? [];
        $preferences['tour_completed'] = true;

        $user->update(['preferences' => $preferences]);
    }

    /**
     * Reset status tour lalu minta JS memulai ulang tour halaman aktif.
     */
    public function resetTour(): void
    {
        $user = auth()->user();

        if ($user !== null) {
            $preferences = $user->preferences ?? [];
            $preferences['tour_completed'] = false;

            $user->update(['preferences' => $preferences]);
        }

        $this->dispatch('admin-tour:start');
    }

    public function render(): mixed
    {
        return view('livewire.admin-tour');
    }
}
