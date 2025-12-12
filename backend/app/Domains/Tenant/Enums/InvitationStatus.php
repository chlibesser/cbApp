<?php

namespace App\Domains\Tenant\Enums;

enum InvitationStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ausstehend',
            self::ACCEPTED => 'Angenommen',
            self::CANCELLED => 'Storniert',
            self::EXPIRED => 'Abgelaufen',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Einladung wurde versendet und wartet auf Annahme',
            self::ACCEPTED => 'Einladung wurde erfolgreich angenommen',
            self::CANCELLED => 'Einladung wurde vom Administrator storniert',
            self::EXPIRED => 'Einladung ist abgelaufen und nicht mehr gültig',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACCEPTED => 'success',
            self::CANCELLED => 'error',
            self::EXPIRED => 'grey',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'mdi-clock-outline',
            self::ACCEPTED => 'mdi-check-circle',
            self::CANCELLED => 'mdi-close-circle',
            self::EXPIRED => 'mdi-alert-circle-outline',
        };
    }

    public function isActive(): bool
    {
        return $this === self::PENDING;
    }

    public function isFinalized(): bool
    {
        return in_array($this, [self::ACCEPTED, self::CANCELLED, self::EXPIRED]);
    }

    public function canBeResent(): bool
    {
        return in_array($this, [self::EXPIRED, self::CANCELLED]);
    }

    public function canBeCancelled(): bool
    {
        return $this === self::PENDING;
    }

    public static function options(): array
    {
        return collect(self::cases())->map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'color' => $case->color(),
            'icon' => $case->icon(),
        ])->toArray();
    }
}