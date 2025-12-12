<?php

namespace App\Core\Category\Enums;

enum CategoryRuleType: string
{
    case KEYWORD = 'keyword';
    case PATTERN = 'pattern';
    case METADATA = 'metadata';
    case CONTENT = 'content';
    case FILENAME = 'filename';

    public function label(): string
    {
        return match($this) {
            self::KEYWORD => 'Schlüsselwort',
            self::PATTERN => 'Muster',
            self::METADATA => 'Metadaten',
            self::CONTENT => 'Inhalt',
            self::FILENAME => 'Dateiname',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::KEYWORD => 'Suche nach spezifischen Wörtern im Dokumentinhalt',
            self::PATTERN => 'Reguläre Ausdrücke oder Text-Muster',
            self::METADATA => 'Eigenschaften wie Dateigröße, Erstellungsdatum, etc.',
            self::CONTENT => 'Volltext-Analyse des Dokumentinhalts',
            self::FILENAME => 'Dateiname-basierte Regeln',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::KEYWORD => 'mdi-format-text',
            self::PATTERN => 'mdi-regex',
            self::METADATA => 'mdi-information',
            self::CONTENT => 'mdi-file-document-outline',
            self::FILENAME => 'mdi-rename-box',
        };
    }

    public function examples(): array
    {
        return match($this) {
            self::KEYWORD => ['Rechnung', 'Invoice', 'Vertrag', 'Contract'],
            self::PATTERN => ['/RE-\d{4}/', '/^\d{2}\.\d{2}\.\d{4}$/', '/[A-Z]{2}\d{6}/'],
            self::METADATA => ['file_size > 1MB', 'created_date < 30 days', 'file_type = PDF'],
            self::CONTENT => ['Enthält Bankverbindung', 'Mietvertrag-Klauseln', 'Datenschutz-Hinweise'],
            self::FILENAME => ['RE_*.pdf', '*_vertrag.docx', 'angebot_*.xlsx'],
        ];
    }
}