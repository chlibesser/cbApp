<?php

namespace Database\Seeders;

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

        // Owner für Personal Tenant
        $maxAccount = Account::firstOrCreate([
            'username' => 'max'
        ], [
            'email' => 'max@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Admin für Company Tenant
        $adminAccount = Account::firstOrCreate([
            'username' => 'admin'
        ], [
            'email' => 'admin@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Buchhalter Full für Company Tenant
        $buchhalterAccount = Account::firstOrCreate([
            'username' => 'buchhalter'
        ], [
            'email' => 'buchhalter@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Buchhalter Read-Only für Company Tenant
        $leseAccount = Account::firstOrCreate([
            'username' => 'lese'
        ], [
            'email' => 'lese@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Basic User für Company Tenant
        $userAccount = Account::firstOrCreate([
            'username' => 'user'
        ], [
            'email' => 'user@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Assign accounts to tenants with roles
        if ($personalTenant && $companyTenant) {
            $ownerRole = \App\Core\Tenant\Models\Role::where('tenant_id', $personalTenant->id)->where('name', 'Owner')->first();
            $adminRole = \App\Core\Tenant\Models\Role::where('tenant_id', $companyTenant->id)->where('name', 'Admin')->first();
            $buchhalterFullRole = \App\Core\Tenant\Models\Role::where('tenant_id', $companyTenant->id)->where('name', 'Buchhalter (Full)')->first();
            $buchhalterReadRole = \App\Core\Tenant\Models\Role::where('tenant_id', $companyTenant->id)->where('name', 'Buchhalter (Read-Only)')->first();
            $userRole = \App\Core\Tenant\Models\Role::where('tenant_id', $companyTenant->id)->where('name', 'User')->first();

            // Max: Owner of personal tenant
            $maxAccount->tenants()->syncWithoutDetaching([$personalTenant->id => ['role_id' => $ownerRole->id]]);

            // Admin: Admin of company tenant
            $adminAccount->tenants()->syncWithoutDetaching([$companyTenant->id => ['role_id' => $adminRole->id]]);

            // Buchhalter: Full access to accounting in company tenant
            $buchhalterAccount->tenants()->syncWithoutDetaching([$companyTenant->id => ['role_id' => $buchhalterFullRole->id]]);

            // Lese: Read-only access to accounting in company tenant
            $leseAccount->tenants()->syncWithoutDetaching([$companyTenant->id => ['role_id' => $buchhalterReadRole->id]]);

            // User: Basic access to company tenant
            $userAccount->tenants()->syncWithoutDetaching([$companyTenant->id => ['role_id' => $userRole->id]]);

            // Create profiles for all users
            $this->createProfile($maxAccount, $personalTenant, 'Max Mustermann', 'Max', 'Mustermann');
            $this->createProfile($adminAccount, $companyTenant, 'Admin User', 'Admin', 'User');
            $this->createProfile($buchhalterAccount, $companyTenant, 'Buchhalter Vollzugriff', 'Buchhalter', 'Vollzugriff');
            $this->createProfile($leseAccount, $companyTenant, 'Buchhalter Readonly', 'Buchhalter', 'Readonly');
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
