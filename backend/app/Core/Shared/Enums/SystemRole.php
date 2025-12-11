<?php

namespace App\Core\Shared\Enums;

/**
 * SystemRole Enum - System-wide roles
 */
enum SystemRole: string
{
    case GLOBAL_ADMIN = 'global_admin';
    case TENANT_ADMIN = 'tenant_admin';
    case TENANT_MEMBER = 'tenant_member';

    public function label(): string
    {
        return match($this) {
            self::GLOBAL_ADMIN => 'Global Administrator',
            self::TENANT_ADMIN => 'Tenant Administrator', 
            self::TENANT_MEMBER => 'Tenant Member',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::GLOBAL_ADMIN => 'Vollzugriff auf das gesamte System und alle Tenants',
            self::TENANT_ADMIN => 'Vollzugriff innerhalb eines Tenants',
            self::TENANT_MEMBER => 'Standard-Benutzerzugriff innerhalb eines Tenants',
        };
    }

    /**
     * Check if this role can manage tenants
     */
    public function canManageTenants(): bool
    {
        return $this === self::GLOBAL_ADMIN;
    }

    /**
     * Check if this role can manage users within a tenant
     */
    public function canManageTenantUsers(): bool
    {
        return in_array($this, [self::GLOBAL_ADMIN, self::TENANT_ADMIN]);
    }

    /**
     * Check if this role can access tenant settings
     */
    public function canAccessTenantSettings(): bool
    {
        return in_array($this, [self::GLOBAL_ADMIN, self::TENANT_ADMIN]);
    }

    /**
     * Get all available roles
     */
    public static function all(): array
    {
        return [
            self::GLOBAL_ADMIN,
            self::TENANT_ADMIN,
            self::TENANT_MEMBER
        ];
    }

    /**
     * Get roles for select dropdown
     */
    public static function forSelect(): array
    {
        return collect(self::all())->map(function($role) {
            return [
                'value' => $role->value,
                'text' => $role->label(),
                'description' => $role->description()
            ];
        })->toArray();
    }
}