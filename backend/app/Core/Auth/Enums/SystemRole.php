<?php

namespace App\Core\Auth\Enums;

enum SystemRole: string
{
    case ADMIN = 'admin';
    case TENANT_ADMIN = 'tenant_admin';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::TENANT_ADMIN => 'Tenant Administrator',
            self::MEMBER => 'Mitglied',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ADMIN => 'Vollzugriff auf alle Tenants und System-Einstellungen',
            self::TENANT_ADMIN => 'Verwaltung des eigenen Tenants (User, Kategorien, Inhalte)',
            self::MEMBER => 'Standard-Benutzer mit Basis-Zugriffsrechten',
        };
    }

    public function canManageTenants(): bool
    {
        return $this === self::ADMIN;
    }

    public function canManageOwnTenant(): bool
    {
        return $this === self::ADMIN || $this === self::TENANT_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isTenantAdmin(): bool
    {
        return $this === self::TENANT_ADMIN;
    }

    public function isMember(): bool
    {
        return $this === self::MEMBER;
    }

    /**
     * Get permissions that this system role grants globally
     */
    public function getGlobalPermissions(): array
    {
        return match ($this) {
            self::ADMIN => [
                'admin.tenants.view',
                'admin.tenants.create', 
                'admin.tenants.edit',
                'admin.tenants.delete',
                'admin.accounts.view',
                'admin.accounts.create',
                'admin.accounts.edit',
                'admin.accounts.delete',
                'admin.system.settings',
            ],
            self::TENANT_ADMIN => [
                'tenant.create',
                'tenant.settings.view',
            ],
            self::MEMBER => [],
        };
    }

    /**
     * Check if this role has a specific global permission
     */
    public function hasGlobalPermission(string $permission): bool
    {
        // Admin hat alle Permissions
        if ($this === self::ADMIN && str_starts_with($permission, 'admin.')) {
            return true;
        }

        return in_array($permission, $this->getGlobalPermissions());
    }

    /**
     * Get all available system roles
     */
    public static function getAllRoles(): array
    {
        return [
            self::ADMIN,
            self::TENANT_ADMIN,
            self::MEMBER,
        ];
    }

    /**
     * Get roles suitable for assignment (exclude ADMIN for security)
     */
    public static function getAssignableRoles(): array
    {
        return [
            self::TENANT_ADMIN,
            self::MEMBER,
        ];
    }
}