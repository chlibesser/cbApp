<?php

namespace App\Core\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Permission extends Model
{
    use HasFactory;
    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'resource',
        'action',
        'description',
    ];

    /**
     * Generate UUID for new models
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

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
