<?php

namespace App\Domains\Tenant\Enums;

enum SystemRole: string
{
    case GLOBAL_ADMIN = 'global_admin';
    case TENANT_ADMIN = 'tenant_admin';
    case TENANT_MEMBER = 'tenant_member';

    public function label(): string
    {
        return match ($this) {
            self::GLOBAL_ADMIN => 'Global Administrator',
            self::TENANT_ADMIN => 'Tenant Administrator',
            self::TENANT_MEMBER => 'Tenant Mitglied',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::GLOBAL_ADMIN => 'Voller Zugriff auf alle Tenants und System-Einstellungen',
            self::TENANT_ADMIN => 'Verwaltung des eigenen Tenants und aller Benutzer',
            self::TENANT_MEMBER => 'Normales Mitglied mit Basis-Zugriffsrechten',
        };
    }

    public function canManageTenantUsers(): bool
    {
        return $this === self::GLOBAL_ADMIN || $this === self::TENANT_ADMIN;
    }

    public function canInviteUsers(): bool
    {
        return $this->canManageTenantUsers();
    }

    public function canManageRoles(): bool
    {
        return $this === self::GLOBAL_ADMIN || $this === self::TENANT_ADMIN;
    }

    public function canDeactivateUsers(): bool
    {
        return $this->canManageTenantUsers();
    }

    public function isGlobalAdmin(): bool
    {
        return $this === self::GLOBAL_ADMIN;
    }

    public function isTenantAdmin(): bool
    {
        return $this === self::TENANT_ADMIN;
    }

    public function isTenantMember(): bool
    {
        return $this === self::TENANT_MEMBER;
    }

    public static function options(): array
    {
        return collect(self::cases())->map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
        ])->toArray();
    }

    public static function tenantOptions(): array
    {
        return collect([self::TENANT_ADMIN, self::TENANT_MEMBER])
            ->map(fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
            ])->toArray();
    }

    public function color(): string
    {
        return match ($this) {
            self::GLOBAL_ADMIN => 'error',
            self::TENANT_ADMIN => 'primary',
            self::TENANT_MEMBER => 'info',
        };
    }
}