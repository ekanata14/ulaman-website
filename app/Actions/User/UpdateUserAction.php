<?php

namespace App\Actions\User;

use App\DTOs\User\UserData;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class UpdateUserAction
{
    public function execute(User $user, UserData $data): bool
    {
        $userData = [
            'name' => $data->name,
            'email' => $data->email,
            'role' => $data->role,
            'departments' => $data->departments,
        ];

        if ($data->profile_photo && ! is_string($data->profile_photo)) {
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $userData['profile_photo'] = $data->profile_photo->store('profile_photo', 'public');
        }

        if (! empty($data->password)) {
            $userData['password'] = bcrypt($data->password);
        }

        return $user->update($userData);
    }
}
