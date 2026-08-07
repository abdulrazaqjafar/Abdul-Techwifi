<?php

namespace App\Policies;

use App\Models\Admin;

class AdminPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->isAdmin();
    }

    public function view(Admin $admin, Admin $staff): bool
    {
        return $admin->isAdmin() && $staff->isCashier();
    }

    public function create(Admin $admin): bool
    {
        return $admin->isAdmin();
    }

    public function update(Admin $admin, Admin $staff): bool
    {
        return $admin->isAdmin() && $staff->isCashier();
    }

    public function delete(Admin $admin, Admin $staff): bool
    {
        return $admin->isAdmin()
            && $staff->isCashier()
            && $staff->id !== $admin->id;
    }
}
