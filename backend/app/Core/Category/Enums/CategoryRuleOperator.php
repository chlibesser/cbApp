<?php

namespace App\Core\Category\Enums;

enum CategoryRuleOperator: string
{
    case CONTAINS = 'contains';
    case EQUALS = 'equals';
    case STARTS_WITH = 'starts_with';
    case ENDS_WITH = 'ends_with';
    case REGEX = 'regex';
    case NOT_CONTAINS = 'not_contains';

    public function label(): string
    {
        return match($this) {
            self::CONTAINS => 'enthält',
            self::EQUALS => 'ist gleich',
            self::STARTS_WITH => 'beginnt mit',
            self::ENDS_WITH => 'endet mit',
            self::REGEX => 'Regex-Pattern',
            self::NOT_CONTAINS => 'enthält nicht',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::CONTAINS => 'Der Text muss den Wert enthalten',
            self::EQUALS => 'Der Text muss exakt mit dem Wert übereinstimmen',
            self::STARTS_WITH => 'Der Text muss mit dem Wert beginnen',
            self::ENDS_WITH => 'Der Text muss mit dem Wert enden',
            self::REGEX => 'Der Text muss dem regulären Ausdruck entsprechen',
            self::NOT_CONTAINS => 'Der Text darf den Wert nicht enthalten',
        };
    }

    public function symbol(): string
    {
        return match($this) {
            self::CONTAINS => '∋',
            self::EQUALS => '=',
            self::STARTS_WITH => 'A*',
            self::ENDS_WITH => '*Z',
            self::REGEX => '/.*/',
            self::NOT_CONTAINS => '∌',
        };
    }

    public function sqlOperator(): string
    {
        return match($this) {
            self::CONTAINS => 'ILIKE',
            self::EQUALS => '=',
            self::STARTS_WITH => 'ILIKE',
            self::ENDS_WITH => 'ILIKE',
            self::REGEX => '~*',
            self::NOT_CONTAINS => 'NOT ILIKE',
        };
    }

    public function formatValue(string $value): string
    {
        return match($this) {
            self::CONTAINS, self::NOT_CONTAINS => "%{$value}%",
            self::STARTS_WITH => "{$value}%",
            self::ENDS_WITH => "%{$value}",
            self::EQUALS, self::REGEX => $value,
        };
    }

    public function isRegex(): bool
    {
        return $this === self::REGEX;
    }

    public function requiresValidation(): bool
    {
        return $this === self::REGEX;
    }
}