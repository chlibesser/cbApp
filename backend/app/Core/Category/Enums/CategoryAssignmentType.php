<?php

namespace App\Core\Category\Enums;

enum CategoryAssignmentType: string
{
    case AI = 'ai';
    case MANUAL = 'manual';
    case RULE = 'rule';
    case HYBRID = 'hybrid';

    public function label(): string
    {
        return match($this) {
            self::AI => 'KI-Zuordnung',
            self::MANUAL => 'Manuelle Zuordnung',
            self::RULE => 'Regel-basiert',
            self::HYBRID => 'KI + Regel',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::AI => 'Automatische Kategorisierung durch Künstliche Intelligenz',
            self::MANUAL => 'Manuelle Zuordnung durch Benutzer',
            self::RULE => 'Automatische Zuordnung durch vordefinierte Regeln',
            self::HYBRID => 'Kombination aus KI-Analyse und Regel-Validierung',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::AI => 'mdi-robot',
            self::MANUAL => 'mdi-account',
            self::RULE => 'mdi-cog',
            self::HYBRID => 'mdi-auto-fix',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::AI => 'primary',
            self::MANUAL => 'grey',
            self::RULE => 'warning',
            self::HYBRID => 'success',
        };
    }

    public function requiresConfidence(): bool
    {
        return in_array($this, [self::AI, self::HYBRID]);
    }

    public function isAutomated(): bool
    {
        return in_array($this, [self::AI, self::RULE, self::HYBRID]);
    }

    public function priority(): int
    {
        return match($this) {
            self::MANUAL => 100,    // Höchste Priorität
            self::HYBRID => 90,
            self::RULE => 80,
            self::AI => 70,         // Niedrigste Priorität
        };
    }
}