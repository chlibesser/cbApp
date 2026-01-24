<?php

namespace App\Core\Shared\Models;

use App\Core\Auth\Models\Account;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableFilter extends BaseModel
{
    protected $fillable = [
        'account_id',
        'table_key',
        'name',
        'color',
        'filter_state',
        'show_as_button',
    ];

    protected $casts = [
        'filter_state' => 'array',
        'show_as_button' => 'boolean',
    ];

    protected $attributes = [
        'color' => '#1976D2',
        'show_as_button' => false,
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeForTable($query, string $tableKey)
    {
        return $query->where('table_key', $tableKey);
    }

    public function scopeForCurrentUser($query)
    {
        return $query->where('account_id', auth()->id());
    }

    public function scopeAsButtons($query)
    {
        return $query->where('show_as_button', true);
    }
}
