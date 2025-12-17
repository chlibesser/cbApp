<?php

namespace App\Domains\Partner\Enums;

enum PartnerStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case POTENTIAL = 'potential';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Aktiv',
            self::INACTIVE => 'Inaktiv',
            self::POTENTIAL => 'Potentiell',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'error',
            self::POTENTIAL => 'warning',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}