<?php

namespace App\Core\Shared\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'resource',
        'action',
        'description',
    ];

    /**
     * Get all roles that have this permission
     */
    public function roles()
    {
        return $this->belongsToMany(\App\Core\Tenant\Models\Role::class, 'role_permissions');
    }

    /**
     * Scope to get permissions for a specific resource
     */
    public function scopeForResource($query, $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope to get permissions for a specific action
     */
    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Check if this permission matches a given resource and action
     */
    public function matches($resource, $action)
    {
        return $this->resource === $resource && $this->action === $action;
    }
}
