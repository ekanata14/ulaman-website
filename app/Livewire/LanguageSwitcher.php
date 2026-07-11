<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageSwitcher extends Component
{
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
