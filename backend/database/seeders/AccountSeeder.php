<?php

namespace Database\Seeders;

use App\Core\Auth\Enums\SystemRole;
use App\Core\Auth\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyTenant = \App\Core\Tenant\Models\Tenant::where('slug', 'demo-firma')->first();
        $personalTenant = \App\Core\Tenant\Models\Tenant::where('slug', 'max-mustermann')->first();

        // Global Admin (kann alles)
        $adminAccount = Account::firstOrCreate([
            'username' => 'admin'
        ], [
            'email' => 'admin@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'system_role' => SystemRole::ADMIN,
        ]);

        // Owner für Personal Tenant (Member mit Owner-Rolle im Tenant)
        $maxAccount = Account::firstOrCreate([
            'username' => 'max'
        ], [
            'email' => 'max@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'system_role' => SystemRole::MEMBER,
        ]);

        // Tenant Admin für Company Tenant
        $tenantAdminAccount = Account::firstOrCreate([
            'username' => 'tenantadmin'
        ], [
            'email' => 'tenantadmin@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'system_role' => SystemRole::TENANT_ADMIN,
        ]);

        // Buchhalter Full für Company Tenant
        $buchhalterAccount = Account::firstOrCreate([
            'username' => 'buchhalter'
        ], [
            'email' => 'buchhalter@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'system_role' => SystemRole::MEMBER,
        ]);

        // Buchhalter Read-Only für Company Tenant
        $leseAccount = Account::firstOrCreate([
            'username' => 'lese'
        ], [
            'email' => 'lese@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'system_role' => SystemRole::MEMBER,
        ]);

        // Basic User für Company Tenant
        $userAccount = Account::firstOrCreate([
            'username' => 'user'
        ], [
            'email' => 'user@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'system_role' => SystemRole::MEMBER,
        ]);

        // Assign accounts to tenants with roles
        if ($personalTenant && $companyTenant) {
            $personalOwnerRole = \App\Core\Tenant\Models\Role::where('tenant_id', $personalTenant->id)->where('name', 'Owner')->first();
            $companyAdminRole = \App\Core\Tenant\Models\Role::where('tenant_id', $companyTenant->id)->where('name', 'Admin')->first();
            $buchhalterFullRole = \App\Core\Tenant\Models\Role::where('tenant_id', $companyTenant->id)->where('name', 'Buchhalter (Full)')->first();
            $buchhalterReadRole = \App\Core\Tenant\Models\Role::where('tenant_id', $companyTenant->id)->where('name', 'Buchhalter (Read-Only)')->first();
            $userRole = \App\Core\Tenant\Models\Role::where('tenant_id', $companyTenant->id)->where('name', 'User')->first();

            // Max: Owner of his personal workspace (selbst-registriert)
            $maxAccount->tenants()->syncWithoutDetaching([$personalTenant->id => ['role_id' => $personalOwnerRole->id]]);
            $this->createProfile($maxAccount, $personalTenant, 'Max Mustermann', 'Max', 'Mustermann');

            // Global Admin: Admin in company tenant (hat System-Role ADMIN, braucht keinen Personal Tenant)
            $adminAccount->tenants()->syncWithoutDetaching([$companyTenant->id => ['role_id' => $companyAdminRole->id]]);
            $this->createProfile($adminAccount, $companyTenant, 'Global Administrator', 'Global', 'Administrator');

            // Tenant Admin: Admin of company tenant (System-Role TENANT_ADMIN)
            $tenantAdminAccount->tenants()->syncWithoutDetaching([$companyTenant->id => ['role_id' => $companyAdminRole->id]]);
            $this->createProfile($tenantAdminAccount, $companyTenant, 'Tenant Administrator', 'Tenant', 'Administrator');

            // Buchhalter: Full access to accounting in company tenant (eingeladen)
            $buchhalterAccount->tenants()->syncWithoutDetaching([$companyTenant->id => ['role_id' => $buchhalterFullRole->id]]);
            $this->createProfile($buchhalterAccount, $companyTenant, 'Buchhalter Vollzugriff', 'Buchhalter', 'Vollzugriff');

            // Lese: Read-only access to accounting in company tenant (eingeladen)
            $leseAccount->tenants()->syncWithoutDetaching([$companyTenant->id => ['role_id' => $buchhalterReadRole->id]]);
            $this->createProfile($leseAccount, $companyTenant, 'Buchhalter Readonly', 'Buchhalter', 'Readonly');

            // User: Basic access to company tenant (eingeladen)
            $userAccount->tenants()->syncWithoutDetaching([$companyTenant->id => ['role_id' => $userRole->id]]);
            $this->createProfile($userAccount, $companyTenant, 'Basic User', 'Basic', 'User');
        }
    }

    private function createProfile($account, $tenant, $displayName, $firstName, $lastName)
    {
        \App\Domains\Identity\Models\Profile::firstOrCreate([
            'account_id' => $account->id,
            'tenant_id' => $tenant->id,
        ], [
            'display_name' => $displayName,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }
}
