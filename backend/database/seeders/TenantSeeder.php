<?php

namespace Database\Seeders;

use App\Core\Tenant\Models\Tenant;
use App\Core\Tenant\Models\Role;
use App\Core\Shared\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Demo Company Tenant
        $companyTenant = Tenant::where('slug', 'demo-firma')->first();
        if (!$companyTenant) {
            $companyTenant = Tenant::create([
                'id' => (string) Str::uuid(),
                'slug' => 'demo-firma',
                'name' => 'Demo Firma GmbH',
                'is_personal' => false,
                'is_active' => true,
            ]);
        }

        // Personal Demo Tenant
        $personalTenant = Tenant::where('slug', 'max-mustermann')->first();
        if (!$personalTenant) {
            $personalTenant = Tenant::create([
                'id' => (string) Str::uuid(),
                'slug' => 'max-mustermann',
                'name' => "Max Mustermann's Workspace",
                'is_personal' => true,
                'is_active' => true,
            ]);
        }

        // Create roles for both tenants
        $this->createRolesForTenant($companyTenant);
        $this->createRolesForTenant($personalTenant);
    }

    private function createRolesForTenant(Tenant $tenant)
    {
        // Owner Role (für Personal Tenants oder Firmen-Owner)
        $ownerRole = Role::where(['tenant_id' => $tenant->id, 'name' => 'Owner'])->first();
        if (!$ownerRole) {
            $ownerRole = Role::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'name' => 'Owner',
                'description' => 'Vollzugriff auf alles',
                'is_system' => true,
            ]);
        }

        // Admin Role
        $adminRole = Role::where(['tenant_id' => $tenant->id, 'name' => 'Admin'])->first();
        if (!$adminRole) {
            $adminRole = Role::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'name' => 'Admin',
                'description' => 'Administrator mit fast allen Rechten',
                'is_system' => true,
            ]);
        }

        // Buchhalter Full Role
        $accountantFullRole = Role::where(['tenant_id' => $tenant->id, 'name' => 'Buchhalter (Full)'])->first();
        if (!$accountantFullRole) {
            $accountantFullRole = Role::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'name' => 'Buchhalter (Full)',
                'description' => 'Vollzugriff auf Buchhaltung',
                'is_system' => true,
            ]);
        }

        // Buchhalter Read-Only Role
        $accountantReadRole = Role::where(['tenant_id' => $tenant->id, 'name' => 'Buchhalter (Read-Only)'])->first();
        if (!$accountantReadRole) {
            $accountantReadRole = Role::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'name' => 'Buchhalter (Read-Only)',
                'description' => 'Nur Lesezugriff auf Buchhaltung',
                'is_system' => true,
            ]);
        }

        // User Role
        $userRole = Role::where(['tenant_id' => $tenant->id, 'name' => 'User'])->first();
        if (!$userRole) {
            $userRole = Role::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'name' => 'User',
                'description' => 'Basis-Benutzer',
                'is_system' => true,
            ]);
        }

        // Assign permissions
        $this->assignPermissions($ownerRole, Permission::all()->pluck('id')->toArray());
        $this->assignPermissions($adminRole, Permission::whereNotIn('resource', ['roles', 'settings'])->pluck('id')->toArray());
        $this->assignPermissions($accountantFullRole, Permission::whereIn('resource', ['accounting', 'reports', 'dashboard'])->pluck('id')->toArray());
        $this->assignPermissions($accountantReadRole, Permission::whereIn('resource', ['accounting', 'reports', 'dashboard'])->whereIn('action', ['view'])->pluck('id')->toArray());
        $this->assignPermissions($userRole, Permission::whereIn('name', ['dashboard.view'])->pluck('id')->toArray());
    }

    private function assignPermissions(Role $role, array $permissionIds)
    {
        $role->permissions()->sync($permissionIds);
    }
}
