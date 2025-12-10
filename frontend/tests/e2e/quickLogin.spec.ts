import { test, expect } from '@playwright/test'
import { testUsers } from './helpers/testUsers'
import { 
  quickLogin, 
  expectToBeOnDashboard,
  expectToBeOnLoginPage
} from './helpers/loginHelpers'

test.describe('Quick Login (Development)', () => {
  test.beforeEach(async ({ page }) => {
    // Setze Development Environment
    await page.addInitScript(() => {
      window.localStorage.setItem('APP_ENV', 'development')
    })
    
    await page.goto('/auth/login')
  })

  test('should show quick login helper in development', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Quick Login Helper sollte sichtbar sein
    await expect(page.locator('[data-testid="quick-login-helper"]')).toBeVisible()
    
    // Titel und Beschreibung
    await expect(page.locator('text=Quick Login (Development)')).toBeVisible()
    await expect(page.locator('text=Schneller Login für Entwicklung')).toBeVisible()
  })

  test('should hide quick login helper in production', async ({ page }) => {
    // Setze Production Environment
    await page.addInitScript(() => {
      window.localStorage.setItem('APP_ENV', 'production')
    })
    
    await page.goto('/auth/login')
    
    // Quick Login Helper sollte NICHT sichtbar sein
    await expect(page.locator('[data-testid="quick-login-helper"]')).not.toBeVisible()
  })

  test('should display available quick login accounts', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Warte auf Quick Login Helper
    await expect(page.locator('[data-testid="quick-login-helper"]')).toBeVisible()
    
    // Verifiziere Account-Liste
    await expect(page.locator('[data-testid="quick-login-accounts"]')).toBeVisible()
    
    // Spezifische Accounts sollten verfügbar sein
    await expect(page.locator('[data-testid="quick-login-admin"]')).toBeVisible()
    await expect(page.locator('[data-testid="quick-login-testuser"]')).toBeVisible()
    
    // Account-Informationen sollten angezeigt werden
    await expect(page.locator('[data-testid="account-info-admin"]')).toContainText('admin@cbapp.ch')
    await expect(page.locator('[data-testid="account-role-admin"]')).toContainText('Global Admin')
  })

  test('should perform quick login with admin account', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Warte auf Quick Login Helper
    await expect(page.locator('[data-testid="quick-login-helper"]')).toBeVisible()
    
    // Klicke auf Admin Quick Login
    await page.click('[data-testid="quick-login-admin"]')
    
    // Sollte zum Dashboard redirecten
    await expectToBeOnDashboard(page)
    
    // Verifiziere dass wir als Admin eingeloggt sind
    await page.click('[data-testid="user-menu"]')
    await expect(page.locator('[data-testid="user-email"]')).toContainText('admin@cbapp.ch')
    await expect(page.locator('[data-testid="user-role"]')).toContainText('Global Admin')
  })

  test('should perform quick login with regular user', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Quick Login mit Regular User
    await page.click('[data-testid="quick-login-testuser"]')
    
    // Sollte zum Dashboard redirecten
    await expectToBeOnDashboard(page)
    
    // Verifiziere User-Informationen
    await page.click('[data-testid="user-menu"]')
    await expect(page.locator('[data-testid="user-email"]')).toContainText('testuser@cbapp.ch')
  })

  test('should handle quick login with tenant selection', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Klicke auf User mit mehreren Tenants
    await page.click('[data-testid="quick-login-multiuser"]')
    
    // Tenant-Auswahl sollte erscheinen
    await expect(page.locator('[data-testid="tenant-selector-modal"]')).toBeVisible()
    await expect(page.locator('[data-testid="tenant-selector"]')).toBeVisible()
    
    // Verfügbare Tenants sollten angezeigt werden
    await expect(page.locator('[data-testid="tenant-option-test-firma-gmbh"]')).toBeVisible()
    await expect(page.locator('[data-testid="tenant-option-andere-firma-ag"]')).toBeVisible()
    
    // Wähle ersten Tenant
    await page.click('[data-testid="tenant-option-test-firma-gmbh"]')
    await page.click('[data-testid="confirm-tenant"]')
    
    // Sollte zum Dashboard redirecten
    await expectToBeOnDashboard(page)
    
    // Verifiziere Tenant-Kontext
    await expect(page.locator('[data-testid="current-tenant"]')).toContainText('Test Firma GmbH')
  })

  test('should show tenant information for each account', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Warte auf Account-Liste
    await expect(page.locator('[data-testid="quick-login-accounts"]')).toBeVisible()
    
    // Verifiziere Tenant-Informationen
    await expect(page.locator('[data-testid="tenant-info-testuser"]')).toContainText('Test Firma GmbH')
    await expect(page.locator('[data-testid="tenant-role-testuser"]')).toContainText('Mitarbeiter')
    
    // Multi-Tenant User sollte mehrere Tenants zeigen
    await expect(page.locator('[data-testid="tenant-info-multiuser"]')).toContainText('2 Tenants')
  })

  test('should show system role information', async ({ page }) => {
    await page.goto('/auth/login')
    
    // System Roles sollten angezeigt werden
    await expect(page.locator('[data-testid="system-role-admin"]')).toContainText('Global Administrator')
    await expect(page.locator('[data-testid="system-role-admin"]')).toHaveClass(/role-global-admin/)
    
    // Regular Users sollten keine System Role haben
    await expect(page.locator('[data-testid="system-role-testuser"]')).toContainText('Kein System-Zugang')
  })

  test('should handle loading states during quick login', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Klicke Quick Login Button
    await page.click('[data-testid="quick-login-admin"]')
    
    // Loading-State sollte angezeigt werden
    await expect(page.locator('[data-testid="quick-login-loading"]')).toBeVisible()
    
    // Quick Login Buttons sollten disabled sein
    await expect(page.locator('[data-testid="quick-login-admin"]')).toBeDisabled()
  })

  test('should handle quick login errors gracefully', async ({ page }) => {
    // Mock API Error
    await page.route('/api/auth/quick-login', (route) => {
      route.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Quick Login nicht verfügbar'
        })
      })
    })
    
    await page.goto('/auth/login')
    
    // Versuche Quick Login
    await page.click('[data-testid="quick-login-admin"]')
    
    // Error Message sollte angezeigt werden
    await expect(page.locator('[data-testid="quick-login-error"]')).toBeVisible()
    await expect(page.locator('[data-testid="quick-login-error"]')).toContainText('Quick Login nicht verfügbar')
    
    // Sollte auf Login-Page bleiben
    await expectToBeOnLoginPage(page)
  })

  test('should refresh account list on reload', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Warte auf Account-Liste
    await expect(page.locator('[data-testid="quick-login-accounts"]')).toBeVisible()
    
    // Reload page
    await page.reload()
    
    // Account-Liste sollte wieder geladen werden
    await expect(page.locator('[data-testid="quick-login-accounts"]')).toBeVisible()
    await expect(page.locator('[data-testid="quick-login-admin"]')).toBeVisible()
  })

  test('should cancel tenant selection', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Klicke auf Multi-Tenant User
    await page.click('[data-testid="quick-login-multiuser"]')
    
    // Tenant-Auswahl sollte erscheinen
    await expect(page.locator('[data-testid="tenant-selector-modal"]')).toBeVisible()
    
    // Cancel Button klicken
    await page.click('[data-testid="cancel-tenant-selection"]')
    
    // Modal sollte geschlossen werden
    await expect(page.locator('[data-testid="tenant-selector-modal"]')).not.toBeVisible()
    
    // Sollte auf Login-Page bleiben
    await expectToBeOnLoginPage(page)
  })

  test('should work in mobile viewport', async ({ page }) => {
    // Setze Mobile Viewport
    await page.setViewportSize({ width: 375, height: 667 })
    
    await page.goto('/auth/login')
    
    // Quick Login Helper sollte auch mobil funktionieren
    await expect(page.locator('[data-testid="quick-login-helper"]')).toBeVisible()
    
    // Account-Liste sollte responsive sein
    await expect(page.locator('[data-testid="quick-login-accounts"]')).toBeVisible()
    
    // Quick Login sollte funktionieren
    await page.click('[data-testid="quick-login-admin"]')
    await expectToBeOnDashboard(page)
  })
})