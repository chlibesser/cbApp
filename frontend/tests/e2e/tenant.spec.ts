import { test, expect } from '@playwright/test'
import { testUsers } from './helpers/testUsers'
import { 
  loginAsUser, 
  expectToBeOnDashboard,
  switchTenant
} from './helpers/loginHelpers'

test.describe('Multi-Tenant Workflows', () => {
  test('should display current tenant context', async ({ page }) => {
    await loginAsUser(page, testUsers.regularUser)
    await expectToBeOnDashboard(page)
    
    // Tenant-Informationen sollten im Header angezeigt werden
    await expect(page.locator('[data-testid="current-tenant"]')).toBeVisible()
    await expect(page.locator('[data-testid="current-tenant"]')).toContainText('Test Firma GmbH')
    
    // Tenant-Role sollte angezeigt werden
    await expect(page.locator('[data-testid="current-role"]')).toContainText('Mitarbeiter')
  })

  test('should switch between tenants for multi-tenant user', async ({ page }) => {
    await loginAsUser(page, testUsers.multiTenantUser)
    await expectToBeOnDashboard(page)
    
    // Aktueller Tenant sollte angezeigt werden
    await expect(page.locator('[data-testid="current-tenant"]')).toBeVisible()
    
    // Öffne Tenant Switcher
    await page.click('[data-testid="tenant-switcher"]')
    
    // Verfügbare Tenants sollten angezeigt werden
    await expect(page.locator('[data-testid="tenant-dropdown"]')).toBeVisible()
    await expect(page.locator('[data-testid="tenant-option-test-firma-gmbh"]')).toBeVisible()
    await expect(page.locator('[data-testid="tenant-option-andere-firma-ag"]')).toBeVisible()
    
    // Wechsle zu anderem Tenant
    await page.click('[data-testid="tenant-option-andere-firma-ag"]')
    
    // Warte auf Tenant-Wechsel
    await page.waitForTimeout(500)
    
    // Verifiziere Tenant-Wechsel
    await expect(page.locator('[data-testid="current-tenant"]')).toContainText('Andere Firma AG')
    
    // URL sollte Tenant-Parameter enthalten
    await expect(page).toHaveURL(/tenant=andere-firma-ag/)
  })

  test('should show tenant-specific permissions', async ({ page }) => {
    await loginAsUser(page, testUsers.tenantAdmin)
    await expectToBeOnDashboard(page)
    
    // Tenant-Admin sollte Admin-Funktionen sehen
    await expect(page.locator('[data-testid="tenant-admin-menu"]')).toBeVisible()
    await expect(page.locator('[data-testid="manage-users"]')).toBeVisible()
    await expect(page.locator('[data-testid="tenant-settings"]')).toBeVisible()
    
    // Aber KEINE Global-Admin Funktionen
    await expect(page.locator('[data-testid="global-admin-menu"]')).not.toBeVisible()
    await expect(page.locator('[data-testid="manage-all-tenants"]')).not.toBeVisible()
  })

  test('should restrict access based on tenant permissions', async ({ page }) => {
    await loginAsUser(page, testUsers.regularUser)
    await expectToBeOnDashboard(page)
    
    // Regular User sollte KEINE Admin-Funktionen sehen
    await expect(page.locator('[data-testid="tenant-admin-menu"]')).not.toBeVisible()
    await expect(page.locator('[data-testid="manage-users"]')).not.toBeVisible()
    
    // Direkter Zugriff auf Admin-Routen sollte blockiert werden
    await page.goto('/admin/tenant/users')
    
    // Sollte Fehler-Page oder Redirect anzeigen
    await expect(page.locator('[data-testid="access-denied"]')).toBeVisible()
  })

  test('should maintain tenant context across navigation', async ({ page }) => {
    await loginAsUser(page, testUsers.multiTenantUser)
    await expectToBeOnDashboard(page)
    
    // Wechsle zu spezifischem Tenant
    await switchTenant(page, 'andere-firma-ag')
    
    // Navigiere zu verschiedenen Seiten
    await page.click('[data-testid="nav-projects"]')
    await expect(page.locator('[data-testid="current-tenant"]')).toContainText('Andere Firma AG')
    
    await page.click('[data-testid="nav-reports"]')
    await expect(page.locator('[data-testid="current-tenant"]')).toContainText('Andere Firma AG')
    
    // Tenant-Kontext sollte persistent bleiben
    await page.reload()
    await expect(page.locator('[data-testid="current-tenant"]')).toContainText('Andere Firma AG')
  })

  test('should show different data per tenant', async ({ page }) => {
    await loginAsUser(page, testUsers.multiTenantUser)
    await expectToBeOnDashboard(page)
    
    // Daten für ersten Tenant
    await expect(page.locator('[data-testid="tenant-data"]')).toContainText('Test Firma GmbH Daten')
    
    // Wechsle Tenant
    await switchTenant(page, 'andere-firma-ag')
    
    // Daten sollten sich ändern
    await expect(page.locator('[data-testid="tenant-data"]')).toContainText('Andere Firma AG Daten')
    
    // Tenant-spezifische Projekte/Workflows sollten unterschiedlich sein
    await page.click('[data-testid="nav-projects"]')
    await expect(page.locator('[data-testid="project-list"]')).not.toContainText('Test Firma GmbH Projekt')
    await expect(page.locator('[data-testid="project-list"]')).toContainText('Andere Firma AG Projekt')
  })

  test('should handle tenant switching errors', async ({ page }) => {
    // Mock API Error für Tenant Switch
    await page.route('/api/profile/switch-tenant', (route) => {
      route.fulfill({
        status: 403,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Kein Zugriff auf diesen Mandanten'
        })
      })
    })
    
    await loginAsUser(page, testUsers.multiTenantUser)
    await expectToBeOnDashboard(page)
    
    // Versuche Tenant-Wechsel
    await page.click('[data-testid="tenant-switcher"]')
    await page.click('[data-testid="tenant-option-andere-firma-ag"]')
    
    // Error Message sollte angezeigt werden
    await expect(page.locator('[data-testid="tenant-switch-error"]')).toBeVisible()
    await expect(page.locator('[data-testid="tenant-switch-error"]')).toContainText('Kein Zugriff auf diesen Mandanten')
    
    // Ursprünglicher Tenant sollte aktiv bleiben
    await expect(page.locator('[data-testid="current-tenant"]')).toContainText('Test Firma GmbH')
  })

  test('should show no tenant switcher for single-tenant users', async ({ page }) => {
    await loginAsUser(page, testUsers.regularUser)
    await expectToBeOnDashboard(page)
    
    // User mit nur einem Tenant sollte keinen Tenant-Switcher sehen
    await expect(page.locator('[data-testid="tenant-switcher"]')).not.toBeVisible()
    
    // Aber Tenant-Name sollte trotzdem angezeigt werden
    await expect(page.locator('[data-testid="current-tenant"]')).toContainText('Test Firma GmbH')
  })

  test('should handle global admin with all tenant access', async ({ page }) => {
    await loginAsUser(page, testUsers.globalAdmin)
    await expectToBeOnDashboard(page)
    
    // Global Admin sollte alle Tenants sehen können
    await page.click('[data-testid="tenant-switcher"]')
    
    // Alle verfügbaren Tenants sollten sichtbar sein
    await expect(page.locator('[data-testid="tenant-dropdown"]')).toBeVisible()
    await expect(page.locator('[data-testid="tenant-option-test-firma-gmbh"]')).toBeVisible()
    await expect(page.locator('[data-testid="tenant-option-andere-firma-ag"]')).toBeVisible()
    await expect(page.locator('[data-testid="tenant-option-dritte-firma-co"]')).toBeVisible()
    
    // Global Admin sollte auch "System-Kontext" haben
    await expect(page.locator('[data-testid="tenant-option-system"]')).toBeVisible()
  })

  test('should persist tenant selection in browser storage', async ({ page }) => {
    await loginAsUser(page, testUsers.multiTenantUser)
    await expectToBeOnDashboard(page)
    
    // Wechsle Tenant
    await switchTenant(page, 'andere-firma-ag')
    
    // Prüfe LocalStorage
    const storedTenant = await page.evaluate(() => {
      return localStorage.getItem('current-tenant')
    })
    expect(storedTenant).toContain('andere-firma-ag')
    
    // Page Refresh sollte Tenant beibehalten
    await page.reload()
    await expect(page.locator('[data-testid="current-tenant"]')).toContainText('Andere Firma AG')
  })

  test('should show tenant-specific branding', async ({ page }) => {
    await loginAsUser(page, testUsers.regularUser)
    await expectToBeOnDashboard(page)
    
    // Tenant-spezifisches Logo/Branding sollte angezeigt werden
    await expect(page.locator('[data-testid="tenant-logo"]')).toHaveAttribute('src', /test-firma-gmbh-logo/)
    
    // Tenant-spezifische Farben/Theme
    await expect(page.locator('[data-testid="app-header"]')).toHaveCSS('background-color', /rgb\(25, 118, 210\)/) // Test Firma Blue
    
    // Wechsle zu anderem Tenant (mit Multi-Tenant User)
    await loginAsUser(page, testUsers.multiTenantUser)
    await switchTenant(page, 'andere-firma-ag')
    
    // Branding sollte sich ändern
    await expect(page.locator('[data-testid="tenant-logo"]')).toHaveAttribute('src', /andere-firma-ag-logo/)
    await expect(page.locator('[data-testid="app-header"]')).toHaveCSS('background-color', /rgb\(76, 175, 80\)/) // Andere Firma Green
  })
})