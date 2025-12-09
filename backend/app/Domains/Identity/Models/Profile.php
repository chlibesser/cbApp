<?php

namespace App\Domains\Identity\Models;

use App\Core\Auth\Models\Account;
use App\Core\Shared\Models\BaseModel;
use App\Core\Tenant\Models\Tenant;

/**
 * Profile Model - Tenant-specific identity
 */
class Profile extends BaseModel
{
    protected $fillable = [
        'account_id',
        'tenant_id',
        'first_name',
        'last_name',
        'display_name',
        'avatar_url',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the account this profile belongs to
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the tenant this profile belongs to
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the roles assigned to this profile
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Get the full name
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}