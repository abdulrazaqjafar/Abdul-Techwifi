<?php

namespace App\Enums;

enum AdminRole: string
{
    case Cashier = 'cashier';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    /**
     * @return list<string>
     */
    public static function adminPanelRoles(): array
    {
        return [self::Admin->value, self::SuperAdmin->value];
    }

    /**
     * @return list<string>
     */
    public static function cashierPanelRoles(): array
    {
        return [self::Cashier->value, self::Admin->value, self::SuperAdmin->value];
    }
}
