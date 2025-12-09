<?php

namespace App\Core\Tenant\Services;

use App\Core\Tenant\Models\Tenant;

/**
 * TenantService - Core tenant management
 */
class TenantService
{
    /**
     * Get the current tenant
     */
    public function current(): ?Tenant
    {
        // This will be implemented to get current tenant from context
        return session('current_tenant');
    }

    /**
     * Set the current tenant
     */
    public function setCurrent(Tenant $tenant): void
    {
        session(['current_tenant' => $tenant]);
    }

    /**
     * Create a new tenant
     */
    public function create(array $data): Tenant
    {
        return Tenant::create([
            'name' => $data['name'],
            'slug' => str($data['name'])->slug(),
            'settings' => $data['settings'] ?? [],
            'is_active' => true,
        ]);
    }
}