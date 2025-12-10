<?php

namespace App\Core\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Get the tenant that owns this role
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all permissions for this role
     */
    public function permissions()
    {
        return $this->belongsToMany(\App\Core\Shared\Models\Permission::class, 'role_permissions');
    }

    /**
     * Get all accounts with this role
     */
    public function accounts()
    {
        return $this->belongsToMany(\App\Core\Auth\Models\Account::class, 'tenant_user');
    }

    /**
     * Scope to get only system roles
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get only custom roles
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }
}
