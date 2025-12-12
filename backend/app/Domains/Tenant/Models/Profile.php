<?php

namespace App\Domains\Tenant\Models;

use App\Core\Auth\Models\Account;
use App\Core\Shared\Models\BaseModel;
use App\Domains\Tenant\Enums\SystemRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'account_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'system_role',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'system_role' => SystemRole::class,
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_active' => false,
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function tenantRoles(): BelongsToMany
    {
        return $this->belongsToMany(TenantRole::class, 'profile_tenant_roles');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isLinkedToAccount(): bool
    {
        return $this->account_id !== null;
    }

    public function isTenantAdmin(): bool
    {
        return $this->system_role === SystemRole::TENANT_ADMIN;
    }

    public function isTenantMember(): bool
    {
        return $this->system_role === SystemRole::TENANT_MEMBER;
    }

    public function hasPermission(string $permissionKey): bool
    {
        foreach ($this->tenantRoles as $tenantRole) {
            if ($tenantRole->hasPermission($permissionKey)) {
                return true;
            }
        }

        return false;
    }

    public function canManageUsers(): bool
    {
        return $this->system_role === SystemRole::TENANT_ADMIN;
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if (!$this->isLinkedToAccount()) {
            return 'invited';
        }
        
        return 'active';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'inactive' => 'Inaktiv',
            'invited' => 'Eingeladen',
            'active' => 'Aktiv',
            default => 'Unbekannt',
        };
    }
}