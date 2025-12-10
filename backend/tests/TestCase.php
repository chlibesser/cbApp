<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed permissions für alle Tests verfügbar machen
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
    }

    /**
     * Erstelle einen Global Admin Account für Tests
     */
    protected function createGlobalAdmin(array $attributes = []): \App\Core\Auth\Models\Account
    {
        return \App\Core\Auth\Models\Account::factory()
            ->globalAdmin()
            ->create($attributes);
    }

    /**
     * Erstelle einen Standard Account für Tests
     */
    protected function createAccount(array $attributes = []): \App\Core\Auth\Models\Account
    {
        return \App\Core\Auth\Models\Account::factory()
            ->create($attributes);
    }

    /**
     * Erstelle einen Tenant mit Admin für Tests
     */
    protected function createTenantWithAdmin(array $tenantAttributes = [], array $adminAttributes = []): array
    {
        $tenant = \App\Core\Tenant\Models\Tenant::factory()->create($tenantAttributes);
        $admin = \App\Core\Auth\Models\Account::factory()->create($adminAttributes);
        
        // Admin zum Tenant hinzufügen mit Admin-Rolle
        $tenant->accounts()->attach($admin->id, [
            'role_id' => \App\Core\Tenant\Models\Role::where('name', 'admin')->first()->id
        ]);

        return ['tenant' => $tenant, 'admin' => $admin];
    }

    /**
     * Authentifiziere einen Account für API-Tests
     */
    protected function actingAsAccount(\App\Core\Auth\Models\Account $account): self
    {
        return $this->actingAs($account, 'sanctum');
    }
}
