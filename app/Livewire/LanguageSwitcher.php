<?php

namespace App\Livewire;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\On;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    /**
     * Reuse dari kontrol bahasa lain (mis. bottom navbar guest) via event.
     */
    #[On('set-locale')]
    public function setLocale(string $locale): mixed
    {
        return $this->changeLocale($locale);
    }

    public function changeLocale(string $locale): mixed
    {
        if (! in_array($locale, ['en', 'id'])) {
            return null;
        }

        Session::put('locale', $locale);
        App::setLocale($locale);

        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }

        return $this->redirect(request()->header('Referer'), navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.language-switcher');
    }
}
