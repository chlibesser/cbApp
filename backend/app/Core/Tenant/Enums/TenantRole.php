<?php

namespace App\Core\Tenant\Enums;

/**
 * Tenant Role Enum
 * Definiert die Rollen innerhalb eines Tenants
 */
enum TenantRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';

    /**
     * Gibt das Label der Rolle zurück
     */
    public function label(): string
    {
        return match($this) {
            self::OWNER => 'Inhaber',
            self::ADMIN => 'Administrator',
            self::MEMBER => 'Mitglied',
        };
    }

    /**
     * Gibt die Beschreibung der Rolle zurück
     */
    public function description(): string
    {
        return match($this) {
            self::OWNER => 'Vollzugriff auf alle Tenant-Funktionen und Einstellungen',
            self::ADMIN => 'Verwaltung von Benutzern und Tenant-Einstellungen',
            self::MEMBER => 'Standardbenutzer mit eingeschränkten Rechten',
        };
    }

    /**
     * Gibt die Berechtigungen der Rolle zurück
     */
    public function permissions(): array
    {
        return match($this) {
            self::OWNER => [
                'tenant.view',
                'tenant.update',
                'tenant.delete',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'roles.view',
                'roles.assign',
                'settings.view',
                'settings.update',
                'billing.view',
                'billing.manage'
            ],
            self::ADMIN => [
                'tenant.view',
                'tenant.update',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'roles.view',
                'roles.assign',
                'settings.view',
                'settings.update'
            ],
            self::MEMBER => [
                'tenant.view',
                'users.view',
                'settings.view'
            ]
        };
    }

    /**
     * Prüft ob die Rolle eine bestimmte Berechtigung hat
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions());
    }

    /**
     * Prüft ob die Rolle Admin-Rechte hat
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN]);
    }

    /**
     * Prüft ob die Rolle Owner ist
     */
    public function isOwner(): bool
    {
        return $this === self::OWNER;
    }

    /**
     * Gibt alle verfügbaren Rollen zurück
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Gibt alle Rollen zurück, die ein Admin zuweisen kann
     */
    public static function assignableByAdmin(): array
    {
        return [
            self::ADMIN,
            self::MEMBER
        ];
    }

    /**
     * Gibt alle Rollen zurück, die ein Owner zuweisen kann
     */
    public static function assignableByOwner(): array
    {
        return self::cases();
    }

    /**
     * Gibt das Level der Rolle zurück (höher = mehr Rechte)
     */
    public function level(): int
    {
        return match($this) {
            self::OWNER => 100,
            self::ADMIN => 50,
            self::MEMBER => 10,
        };
    }

    /**
     * Prüft ob diese Rolle höher ist als eine andere
     */
    public function isHigherThan(self $other): bool
    {
        return $this->level() > $other->level();
    }

    /**
     * Prüft ob diese Rolle mindestens so hoch ist wie eine andere
     */
    public function isAtLeast(self $other): bool
    {
        return $this->level() >= $other->level();
    }
}