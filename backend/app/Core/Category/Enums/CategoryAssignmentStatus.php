<?php

namespace App\Core\Category\Enums;

enum CategoryAssignmentStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case AUTO_APPROVED = 'auto_approved';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Wartend',
            self::APPROVED => 'Bestätigt',
            self::REJECTED => 'Abgelehnt',
            self::AUTO_APPROVED => 'Auto-Bestätigt',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::PENDING => 'Wartet auf manuelle Bestätigung',
            self::APPROVED => 'Manuell bestätigt und gültig',
            self::REJECTED => 'Abgelehnt, Kategorie-Zuordnung ungültig',
            self::AUTO_APPROVED => 'Automatisch bestätigt (hohe Konfidenz)',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'error',
            self::AUTO_APPROVED => 'info',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'mdi-clock-outline',
            self::APPROVED => 'mdi-check-circle',
            self::REJECTED => 'mdi-close-circle',
            self::AUTO_APPROVED => 'mdi-check-circle-outline',
        };
    }

    public function isApproved(): bool
    {
        return in_array($this, [self::APPROVED, self::AUTO_APPROVED]);
    }

    public function requiresAction(): bool
    {
        return $this === self::PENDING;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED, self::AUTO_APPROVED]);
    }

    public function canBeChanged(): bool
    {
        return $this !== self::AUTO_APPROVED;
    }

    public static function getActiveStates(): array
    {
        return [self::APPROVED, self::AUTO_APPROVED];
    }

    public static function getPendingStates(): array
    {
        return [self::PENDING];
    }

    public static function getRejectedStates(): array
    {
        return [self::REJECTED];
    }
}