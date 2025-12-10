<?php

namespace Database\Seeders;

use App\Core\Shared\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Buchhaltung
            ['name' => 'accounting.view', 'resource' => 'accounting', 'action' => 'view', 'description' => 'Buchhaltung anzeigen'],
            ['name' => 'accounting.create', 'resource' => 'accounting', 'action' => 'create', 'description' => 'Buchungen erstellen'],
            ['name' => 'accounting.edit', 'resource' => 'accounting', 'action' => 'edit', 'description' => 'Buchungen bearbeiten'],
            ['name' => 'accounting.delete', 'resource' => 'accounting', 'action' => 'delete', 'description' => 'Buchungen löschen'],

            // Reports
            ['name' => 'reports.view', 'resource' => 'reports', 'action' => 'view', 'description' => 'Reports anzeigen'],
            ['name' => 'reports.export', 'resource' => 'reports', 'action' => 'export', 'description' => 'Reports exportieren'],

            // User Management
            ['name' => 'users.view', 'resource' => 'users', 'action' => 'view', 'description' => 'Benutzer anzeigen'],
            ['name' => 'users.invite', 'resource' => 'users', 'action' => 'invite', 'description' => 'Benutzer einladen'],
            ['name' => 'users.edit', 'resource' => 'users', 'action' => 'edit', 'description' => 'Benutzer bearbeiten'],
            ['name' => 'users.delete', 'resource' => 'users', 'action' => 'delete', 'description' => 'Benutzer löschen'],

            // Settings
            ['name' => 'settings.view', 'resource' => 'settings', 'action' => 'view', 'description' => 'Einstellungen anzeigen'],
            ['name' => 'settings.edit', 'resource' => 'settings', 'action' => 'edit', 'description' => 'Einstellungen bearbeiten'],

            // Roles & Permissions
            ['name' => 'roles.view', 'resource' => 'roles', 'action' => 'view', 'description' => 'Rollen anzeigen'],
            ['name' => 'roles.create', 'resource' => 'roles', 'action' => 'create', 'description' => 'Rollen erstellen'],
            ['name' => 'roles.edit', 'resource' => 'roles', 'action' => 'edit', 'description' => 'Rollen bearbeiten'],
            ['name' => 'roles.delete', 'resource' => 'roles', 'action' => 'delete', 'description' => 'Rollen löschen'],

            // Dashboard
            ['name' => 'dashboard.view', 'resource' => 'dashboard', 'action' => 'view', 'description' => 'Dashboard anzeigen'],
        ];

        foreach ($permissions as $permission) {
            $existing = Permission::where('name', $permission['name'])->first();
            
            if (!$existing) {
                Permission::create(array_merge($permission, [
                    'id' => (string) Str::uuid()
                ]));
            }
        }
    }
}
