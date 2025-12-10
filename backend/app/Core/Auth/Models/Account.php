<?php

namespace App\Core\Auth\Models;

use App\Core\Shared\Models\BaseModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Account Model - Global Login Entity
 * 
 * Represents the global authentication account with:
 * - Unique username for login
 * - Unique email for login and verification
 * - Password authentication
 * - API token management via Sanctum
 * 
 * An Account can have multiple tenant-specific Profiles.
 */
class Account extends BaseModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasApiTokens, HasUuids;

    protected $fillable = [
        'username',
        'email', 
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Get the profiles associated with this account.
     */
    public function profiles()
    {
        return $this->hasMany(\App\Domains\Identity\Models\Profile::class);
    }

    /**
     * Find account by username or email.
     */
    public static function findByUsernameOrEmail(string $identifier)
    {
        return static::where('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();
    }

    /**
     * Check if account is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if email is verified.
     */
    public function isEmailVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Get all tenants associated with this account
     */
    public function tenants()
    {
        return $this->belongsToMany(\App\Core\Tenant\Models\Tenant::class, 'tenant_user')
                    ->withPivot('role_id')
                    ->withTimestamps();
    }

    /**
     * Get the role for a specific tenant
     */
    public function getRoleForTenant($tenantId)
    {
        $pivot = $this->tenants()->where('tenant_id', $tenantId)->first()?->pivot;
        return $pivot ? \App\Core\Tenant\Models\Role::find($pivot->role_id) : null;
    }

    /**
     * Check if account has permission for a specific tenant
     */
    public function hasPermission($tenantId, $resource, $action)
    {
        $role = $this->getRoleForTenant($tenantId);
        if (!$role) return false;

        return $role->permissions()
                   ->where('resource', $resource)
                   ->where('action', $action)
                   ->exists();
    }

    /**
     * Get all permissions for a specific tenant
     */
    public function getPermissionsForTenant($tenantId)
    {
        $role = $this->getRoleForTenant($tenantId);
        return $role ? $role->permissions : collect();
    }
}