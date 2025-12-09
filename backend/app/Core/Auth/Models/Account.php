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
}