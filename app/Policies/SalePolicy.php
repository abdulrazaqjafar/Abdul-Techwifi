<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Sale;

class SalePolicy
{
    public function viewAny(Admin $admin): bool
    {
        return in_array($admin->role, ['admin', 'super_admin', 'cashier'], true);
    }

    public function view(Admin $admin, Sale $sale): bool
    {
        if (in_array($admin->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        return $admin->isCashier() && $sale->admin_id === $admin->id;
    }

    public function create(Admin $admin): bool
    {
        return in_array($admin->role, ['admin', 'super_admin', 'cashier'], true);
    }

    public function export(Admin $admin): bool
    {
        return in_array($admin->role, ['admin', 'super_admin'], true);
    }
}
