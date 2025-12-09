<?php

namespace App\Core\Tenant\Models;

use App\Core\Shared\Models\BaseModel;

/**
 * Tenant Model - Multi-tenancy core
 */
class Tenant extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all profiles belonging to this tenant
     */
    public function profiles()
    {
        return $this->hasMany(\App\Domains\Identity\Models\Profile::class);
    }
}