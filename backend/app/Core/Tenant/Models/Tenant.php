<?php

namespace App\Core\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tenant Model - Multi-tenancy core
 */
class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_personal',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_personal' => 'boolean',
    ];

    /**
     * Get all profiles belonging to this tenant
     */
    public function profiles()
    {
        return $this->hasMany(\App\Domains\Identity\Models\Profile::class);
    }

    /**
     * Get all accounts belonging to this tenant
     */
    public function accounts()
    {
        return $this->belongsToMany(\App\Core\Auth\Models\Account::class, 'tenant_user')
                    ->withPivot('role_id')
                    ->withTimestamps();
    }

    /**
     * Get all roles belonging to this tenant
     */
    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Scope to get only personal tenants
     */
    public function scopePersonal($query)
    {
        return $query->where('is_personal', true);
    }

    /**
     * Scope to get only company tenants
     */
    public function scopeCompany($query)
    {
        return $query->where('is_personal', false);
    }
}