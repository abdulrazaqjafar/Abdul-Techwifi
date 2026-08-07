<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Voucher;

class VoucherPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return true;
    }

    public function view(Admin $admin, Voucher $voucher): bool
    {
        return true;
    }

    public function create(Admin $admin): bool
    {
        return in_array($admin->role, ['admin', 'super_admin', 'cashier'], true);
    }

    public function update(Admin $admin, Voucher $voucher): bool
    {
        return in_array($admin->role, ['admin', 'super_admin'], true);
    }

    public function delete(Admin $admin, Voucher $voucher): bool
    {
        return $voucher->isUnused() && in_array($admin->role, ['admin', 'super_admin'], true);
    }

    public function export(Admin $admin): bool
    {
        return true;
    }
}
