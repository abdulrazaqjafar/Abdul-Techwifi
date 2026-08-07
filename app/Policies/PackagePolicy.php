<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Package;

class PackagePolicy
{
    public function viewAny(Admin $admin): bool
    {
        return true;
    }

    public function view(Admin $admin, Package $package): bool
    {
        return true;
    }

    public function create(Admin $admin): bool
    {
        return in_array($admin->role, ['admin', 'super_admin'], true);
    }

    public function update(Admin $admin, Package $package): bool
    {
        return in_array($admin->role, ['admin', 'super_admin'], true);
    }

    public function delete(Admin $admin, Package $package): bool
    {
        return $admin->role === 'super_admin';
    }
}
