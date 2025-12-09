<?php

namespace App\Core\Auth\Models;

use App\Core\Shared\Models\BaseModel;

/**
 * Session Model - Session Management
 */
class Session extends BaseModel
{
    protected $fillable = [
        'account_id',
        'token',
        'ip_address',
        'user_agent',
        'last_activity',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}