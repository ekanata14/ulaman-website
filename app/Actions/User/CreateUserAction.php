<?php

namespace App\Actions\User;

use App\DTOs\User\UserData;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class CreateUserAction
{
    public function execute(UserData $data): User
    {
        $userData = [
            'name' => $data->name,
            'email' => $data->email,
            'role' => $data->role,
            'password' => bcrypt($data->password),
            'departments' => $data->departments,
        ];

        if ($data->profile_photo instanceof UploadedFile) {
            $userData['profile_photo'] = $data->profile_photo->store('profile_photo', 'public');
        }

        return User::create($userData);
    }
}
