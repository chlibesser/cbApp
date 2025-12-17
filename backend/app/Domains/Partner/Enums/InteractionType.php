<?php

namespace App\Domains\Partner\Enums;

enum InteractionType: string
{
    case MEETING = 'meeting';
    case CALL = 'call';
    case EMAIL = 'email';
    case NOTE = 'note';
    case DOCUMENT = 'document';

    public function label(): string
    {
        return match($this) {
            self::MEETING => 'Meeting',
            self::CALL => 'Telefonat',
            self::EMAIL => 'E-Mail',
            self::NOTE => 'Notiz',
            self::DOCUMENT => 'Dokument',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::MEETING => 'mdi-account-group',
            self::CALL => 'mdi-phone',
            self::EMAIL => 'mdi-email',
            self::NOTE => 'mdi-note-text',
            self::DOCUMENT => 'mdi-file-document',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value, 
                'label' => $case->label(),
                'icon' => $case->icon()
            ],
            self::cases()
        );
    }
}