<?php

namespace App\Domains\Identity\Models;

use App\Core\Shared\Models\BaseModel;
use App\Core\Tenant\Models\Tenant;

/**
 * Role Model - Tenant-specific roles
 */
class Role extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'permissions',
        'is_system',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
    ];

    /**
     * Get the tenant this role belongs to
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the profiles that have this role
     */
    public function profiles()
    {
        return $this->belongsToMany(Profile::class);
    }
}