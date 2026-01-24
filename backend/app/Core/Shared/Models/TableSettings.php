<?php

namespace App\Core\Shared\Models;

use App\Core\Auth\Models\Account;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TableSettings - Benutzer-spezifische Tabelleneinstellungen
 *
 * Speichert Spaltenreihenfolge und -breiten pro User und Tabelle.
 */
class TableSettings extends Model
{
    use HasUuids;

    protected $table = 'table_settings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'account_id',
        'table_key',
        'column_order',
        'column_widths',
    ];

    protected $casts = [
        'column_order' => 'array',
        'column_widths' => 'array',
    ];

    /**
     * Beziehung zum Account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope: Für eine bestimmte Tabelle
     */
    public function scopeForTable($query, string $tableKey)
    {
        return $query->where('table_key', $tableKey);
    }

    /**
     * Scope: Für den aktuellen Benutzer
     */
    public function scopeForCurrentUser($query)
    {
        return $query->where('account_id', auth()->id());
    }

    /**
     * Holt oder erstellt Settings für einen User und eine Tabelle
     */
    public static function getOrCreate(string $tableKey, ?string $accountId = null): self
    {
        $accountId = $accountId ?? auth()->id();

        return self::firstOrCreate(
            [
                'account_id' => $accountId,
                'table_key' => $tableKey,
            ],
            [
                'column_order' => null,
                'column_widths' => null,
            ]
        );
    }
}
