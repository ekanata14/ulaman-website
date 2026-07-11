<?php

use App\Models\User;

it('applies locale from session', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);

    $this->actingAs($user)
        ->withSession(['locale' => 'id'])
        ->get(route('admin.dashboard'));

    expect(app()->getLocale())->toBe('id');
});

it('falls back to user locale from database and syncs to session', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'email_verified_at' => now(),
        'locale' => 'id',
    ]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    expect(app()->getLocale())->toBe('id');
    $response->assertSessionHas('locale', 'id');
});
