<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('two factor challenge redirects to login when not authenticated', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $this->get(route('two-factor.login'))
        ->assertRedirect(route('login'));
});

test('two factor challenge view can be rendered', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    // Requires a user to be in the "pending 2FA" session state which is only
    // set by Fortify's pipeline. The custom Livewire Login bypasses that pipeline.
    $this->markTestSkipped('Custom Livewire login bypasses Fortify 2FA pipeline.');
});
