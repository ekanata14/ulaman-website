<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Otorisasi UPL: super_admin & admin aktif boleh mengelola data operasional.
 */
trait AdminManages
{
    protected function isManager(User $user): bool
    {
        return $user->is_active && in_array($user->role, ['super_admin', 'admin'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->isManager($user);
    }

    public function view(User $user): bool
    {
        return $this->isManager($user);
    }

    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(User $user): bool
    {
        return $this->isManager($user);
    }

    public function delete(User $user): bool
    {
        return $this->isManager($user);
    }
}
