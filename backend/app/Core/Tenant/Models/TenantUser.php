<?php

namespace App\Core\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * TenantUser Model - Explicit Pivot Model for tenant_user table
 * 
 * Represents the many-to-many relationship between Accounts and Tenants
 * with additional role information.
 */
class TenantUser extends Pivot
{
    use HasUuids;

    protected $table = 'tenant_user';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'role_id',
    ];

    /**
     * Get the tenant this pivot belongs to
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the account this pivot belongs to
     */
    public function account()
    {
        return $this->belongsTo(\App\Core\Auth\Models\Account::class);
    }

    /**
     * Get the role assigned in this tenant
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}