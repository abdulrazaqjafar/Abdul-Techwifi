<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Customer;

class CustomerPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return true;
    }

    public function view(Admin $admin, Customer $customer): bool
    {
        return true;
    }

    public function create(Admin $admin): bool
    {
        return in_array($admin->role, ['admin', 'super_admin'], true);
    }

    public function update(Admin $admin, Customer $customer): bool
    {
        return in_array($admin->role, ['admin', 'super_admin'], true);
    }

    public function delete(Admin $admin, Customer $customer): bool
    {
        return $admin->role === 'super_admin';
    }

    public function export(Admin $admin): bool
    {
        return true;
    }
}
