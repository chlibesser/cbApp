<?php

namespace App\Core\Shared\Enums;

/**
 * SystemRole Enum - System-wide roles
 */
enum SystemRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case TENANT_ADMIN = 'tenant_admin';
    case USER = 'user';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::TENANT_ADMIN => 'Tenant Administrator',
            self::USER => 'User',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Full system access across all tenants',
            self::TENANT_ADMIN => 'Full access within a tenant',
            self::USER => 'Standard user access',
        };
    }
}