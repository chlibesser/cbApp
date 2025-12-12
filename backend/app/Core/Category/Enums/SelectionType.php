<?php

namespace App\Core\Category\Enums;

enum SelectionType: string
{
    case SINGLE = 'single';
    case MULTI = 'multi';
    
    public function label(): string
    {
        return match($this) {
            self::SINGLE => 'Einfachauswahl',
            self::MULTI => 'Mehrfachauswahl',
        };
    }
    
    public function description(): string
    {
        return match($this) {
            self::SINGLE => 'Nur eine Kategorie kann ausgewählt werden',
            self::MULTI => 'Mehrere Kategorien können ausgewählt werden',
        };
    }
}