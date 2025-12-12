<?php

namespace App\Domains\Tenant\Models;

use App\Core\Auth\Models\Account;
use App\Core\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'description',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }

    public function activeProfiles(): HasMany
    {
        return $this->profiles()->where('is_active', true);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->pending();
    }

    public function accounts(): HasManyThrough
    {
        return $this->hasManyThrough(
            Account::class,
            Profile::class,
            'tenant_id',
            'id',
            'id',
            'account_id'
        )->whereNotNull('profiles.account_id')
            ->distinct();
    }

    public function tenantAdmins(): HasMany
    {
        return $this->profiles()
            ->where('system_role', 'tenant_admin')
            ->where('is_active', true);
    }

    public function tenantMembers(): HasMany
    {
        return $this->profiles()
            ->where('system_role', 'tenant_member')
            ->where('is_active', true);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->settings['features'] ?? [];
        return in_array($feature, $features);
    }

    public function getUserCount(): int
    {
        return $this->activeProfiles()->count();
    }

    public function getPendingInvitationCount(): int
    {
        return $this->pendingInvitations()->count();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }
}