<?php

use App\Actions\User\CreateUserAction;
use App\DTOs\User\UserData;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('creates a user with the correct attributes', function () {
    $dto = new UserData(
        name: 'Jane Doe',
        email: 'jane@example.com',
        role: 'user',
        password: 'password',
    );

    $user = app(CreateUserAction::class)->execute($dto);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Jane Doe')
        ->and($user->email)->toBe('jane@example.com')
        ->and($user->role)->toBe('user');

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
});

it('stores a profile photo when provided', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('avatar.jpg');

    $dto = new UserData(
        name: 'Jane Doe',
        email: 'jane@example.com',
        role: 'user',
        password: 'password',
        profile_photo: $file,
    );

    $user = app(CreateUserAction::class)->execute($dto);

    expect($user->profile_photo)->not->toBeNull();
    Storage::disk('public')->assertExists($user->profile_photo);
});
