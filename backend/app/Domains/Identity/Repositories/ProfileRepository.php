<?php

namespace App\Domains\Identity\Repositories;

use App\Core\Tenant\Models\Tenant;
use App\Domains\Identity\Models\Profile;

/**
 * ProfileRepository - Data access for profiles
 */
class ProfileRepository
{
    /**
     * Find profiles by tenant
     */
    public function findByTenant(Tenant $tenant): \Illuminate\Database\Eloquent\Collection
    {
        return Profile::where('tenant_id', $tenant->id)
            ->with(['account', 'roles'])
            ->get();
    }

    /**
     * Find active profiles by tenant
     */
    public function findActiveByTenant(Tenant $tenant): \Illuminate\Database\Eloquent\Collection
    {
        return Profile::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->with(['account', 'roles'])
            ->get();
    }

    /**
     * Search profiles
     */
    public function search(string $query, ?Tenant $tenant = null): \Illuminate\Database\Eloquent\Collection
    {
        $builder = Profile::query()
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('display_name', 'like', "%{$query}%");
            });

        if ($tenant) {
            $builder->where('tenant_id', $tenant->id);
        }

        return $builder->with(['account', 'tenant'])->get();
    }
}