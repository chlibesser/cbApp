<?php

namespace Tests\Unit\Core\Shared\Enums;

use Tests\TestCase;
use App\Core\Shared\Enums\SystemRole;

class SystemRoleTest extends TestCase
{
    /** @test */
    public function it_provides_correct_labels(): void
    {
        $this->assertEquals('Global Administrator', SystemRole::GLOBAL_ADMIN->label());
        
        // Add other roles as they are defined
        // $this->assertEquals('Tenant Administrator', SystemRole::TENANT_ADMIN->label());
        // $this->assertEquals('Support Manager', SystemRole::SUPPORT->label());
    }

    /** @test */
    public function it_provides_correct_descriptions(): void
    {
        $description = SystemRole::GLOBAL_ADMIN->description();
        
        $this->assertIsString($description);
        $this->assertStringContainsString('Vollzugriff', $description);
        $this->assertStringContainsString('System', $description);
    }

    /** @test */
    public function it_checks_tenant_management_permissions(): void
    {
        $this->assertTrue(SystemRole::GLOBAL_ADMIN->canManageTenants());
        
        // Add when other roles exist
        // $this->assertTrue(SystemRole::TENANT_ADMIN->canManageTenants());
        // $this->assertFalse(SystemRole::SUPPORT->canManageTenants());
    }

    /** @test */
    public function it_checks_admin_status(): void
    {
        $this->assertTrue(SystemRole::GLOBAL_ADMIN->isAdmin());
        
        // Add when other roles exist
        // $this->assertTrue(SystemRole::TENANT_ADMIN->isAdmin());
        // $this->assertFalse(SystemRole::SUPPORT->isAdmin());
    }

    /** @test */
    public function it_gets_global_permissions(): void
    {
        $permissions = SystemRole::GLOBAL_ADMIN->getGlobalPermissions();
        
        $this->assertIsArray($permissions);
        $this->assertContains('manage_all_tenants', $permissions);
        $this->assertContains('manage_all_accounts', $permissions);
        $this->assertContains('access_admin_panel', $permissions);
        $this->assertContains('manage_system_settings', $permissions);
    }

    /** @test */
    public function it_checks_specific_global_permissions(): void
    {
        $globalAdmin = SystemRole::GLOBAL_ADMIN;
        
        $this->assertTrue($globalAdmin->hasGlobalPermission('manage_all_tenants'));
        $this->assertTrue($globalAdmin->hasGlobalPermission('manage_all_accounts'));
        $this->assertTrue($globalAdmin->hasGlobalPermission('access_admin_panel'));
        $this->assertTrue($globalAdmin->hasGlobalPermission('manage_system_settings'));
        
        $this->assertFalse($globalAdmin->hasGlobalPermission('nonexistent_permission'));
    }

    /** @test */
    public function it_gets_all_available_roles(): void
    {
        $allRoles = SystemRole::all();
        
        $this->assertIsArray($allRoles);
        $this->assertContains(SystemRole::GLOBAL_ADMIN, $allRoles);
        $this->assertGreaterThanOrEqual(1, count($allRoles));
    }

    /** @test */
    public function it_gets_assignable_roles(): void
    {
        // Global Admin kann alle Rollen zuweisen
        $assignableByGlobalAdmin = SystemRole::getAssignableRoles(SystemRole::GLOBAL_ADMIN);
        $this->assertIsArray($assignableByGlobalAdmin);
        $this->assertContains(SystemRole::GLOBAL_ADMIN, $assignableByGlobalAdmin);
        
        // Add when other roles exist
        // $assignableByTenantAdmin = SystemRole::getAssignableRoles(SystemRole::TENANT_ADMIN);
        // $this->assertNotContains(SystemRole::GLOBAL_ADMIN, $assignableByTenantAdmin);
    }

    /** @test */
    public function it_checks_role_hierarchy(): void
    {
        // Global Admin hat höchste Berechtigung
        $this->assertTrue(SystemRole::GLOBAL_ADMIN->hasHigherOrEqualPrivilegesThan(SystemRole::GLOBAL_ADMIN));
        
        // Add when other roles exist
        // $this->assertTrue(SystemRole::GLOBAL_ADMIN->hasHigherOrEqualPrivilegesThan(SystemRole::TENANT_ADMIN));
        // $this->assertFalse(SystemRole::TENANT_ADMIN->hasHigherOrEqualPrivilegesThan(SystemRole::GLOBAL_ADMIN));
    }

    /** @test */
    public function it_gets_role_level(): void
    {
        $this->assertEquals(100, SystemRole::GLOBAL_ADMIN->getLevel());
        
        // Add when other roles exist
        // $this->assertEquals(50, SystemRole::TENANT_ADMIN->getLevel());
        // $this->assertEquals(25, SystemRole::SUPPORT->getLevel());
    }

    /** @test */
    public function it_checks_system_access(): void
    {
        $this->assertTrue(SystemRole::GLOBAL_ADMIN->hasSystemAccess());
        
        // Add when other roles exist
        // $this->assertTrue(SystemRole::SUPPORT->hasSystemAccess());
        // $this->assertFalse(SystemRole::TENANT_ADMIN->hasSystemAccess());
    }

    /** @test */
    public function it_gets_allowed_actions(): void
    {
        $actions = SystemRole::GLOBAL_ADMIN->getAllowedActions();
        
        $this->assertIsArray($actions);
        $this->assertContains('create', $actions);
        $this->assertContains('read', $actions);
        $this->assertContains('update', $actions);
        $this->assertContains('delete', $actions);
        $this->assertContains('manage', $actions);
    }

    /** @test */
    public function it_checks_specific_actions(): void
    {
        $globalAdmin = SystemRole::GLOBAL_ADMIN;
        
        $this->assertTrue($globalAdmin->canPerformAction('create'));
        $this->assertTrue($globalAdmin->canPerformAction('read'));
        $this->assertTrue($globalAdmin->canPerformAction('update'));
        $this->assertTrue($globalAdmin->canPerformAction('delete'));
        $this->assertTrue($globalAdmin->canPerformAction('manage'));
    }

    /** @test */
    public function it_gets_css_class_for_ui(): void
    {
        $this->assertEquals('role-global-admin', SystemRole::GLOBAL_ADMIN->getCssClass());
        
        // Add when other roles exist
        // $this->assertEquals('role-tenant-admin', SystemRole::TENANT_ADMIN->getCssClass());
    }

    /** @test */
    public function it_gets_icon_for_ui(): void
    {
        $icon = SystemRole::GLOBAL_ADMIN->getIcon();
        
        $this->assertIsString($icon);
        $this->assertStringContainsString('mdi-', $icon); // Material Design Icons
    }

    /** @test */
    public function it_serializes_to_string(): void
    {
        $this->assertEquals('global_admin', (string) SystemRole::GLOBAL_ADMIN);
        $this->assertEquals('global_admin', SystemRole::GLOBAL_ADMIN->value);
    }

    /** @test */
    public function it_creates_from_string(): void
    {
        $role = SystemRole::from('global_admin');
        $this->assertEquals(SystemRole::GLOBAL_ADMIN, $role);
    }

    /** @test */
    public function it_handles_invalid_role_strings(): void
    {
        $this->expectException(\ValueError::class);
        SystemRole::from('invalid_role');
    }

    /** @test */
    public function it_tries_from_string_safely(): void
    {
        $validRole = SystemRole::tryFrom('global_admin');
        $this->assertEquals(SystemRole::GLOBAL_ADMIN, $validRole);

        $invalidRole = SystemRole::tryFrom('invalid_role');
        $this->assertNull($invalidRole);
    }
}