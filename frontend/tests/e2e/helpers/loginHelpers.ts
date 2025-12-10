import { Page, expect } from '@playwright/test'
import { TestUser } from './testUsers'

export async function loginAsUser(page: Page, user: TestUser) {
  await page.goto('/auth/login')
  
  // Warte auf Login-Form
  await expect(page.locator('[data-testid="login-form"]')).toBeVisible()
  
  // Fülle Login-Daten aus
  await page.fill('[data-testid="login-identifier"]', user.identifier)
  await page.fill('[data-testid="login-password"]', user.password)
  
  // Klicke Login Button
  await page.click('[data-testid="login-submit"]')
  
  // Warte auf erfolgreichen Login (Redirect zum Dashboard)
  await page.waitForURL('/dashboard')
  
  // Verifiziere dass Login erfolgreich war
  await expect(page.locator('[data-testid="user-menu"]')).toBeVisible()
}

export async function quickLogin(page: Page, username: string, tenantId?: string) {
  await page.goto('/auth/login')
  
  // Warte auf Quick Login Helper
  await expect(page.locator('[data-testid="quick-login-helper"]')).toBeVisible()
  
  // Klicke auf Quick Login Button für den User
  const quickLoginSelector = `[data-testid="quick-login-${username}"]`
  await page.click(quickLoginSelector)
  
  // Wenn Tenant-Auswahl nötig, wähle Tenant
  if (tenantId) {
    await expect(page.locator('[data-testid="tenant-selector"]')).toBeVisible()
    await page.selectOption('[data-testid="tenant-selector"]', tenantId)
    await page.click('[data-testid="confirm-tenant"]')
  }
  
  // Warte auf Redirect zum Dashboard
  await page.waitForURL('/dashboard')
  
  // Verifiziere Login-Status
  await expect(page.locator('[data-testid="user-menu"]')).toBeVisible()
}

export async function logout(page: Page) {
  // Öffne User-Menu
  await page.click('[data-testid="user-menu"]')
  
  // Klicke Logout
  await page.click('[data-testid="logout-button"]')
  
  // Warte auf Redirect zu Login
  await page.waitForURL('/auth/login')
  
  // Verifiziere dass wir ausgeloggt sind
  await expect(page.locator('[data-testid="login-form"]')).toBeVisible()
}

export async function expectToBeOnLoginPage(page: Page) {
  await expect(page).toHaveURL('/auth/login')
  await expect(page.locator('[data-testid="login-form"]')).toBeVisible()
}

export async function expectToBeOnDashboard(page: Page) {
  await expect(page).toHaveURL('/dashboard')
  await expect(page.locator('[data-testid="dashboard-content"]')).toBeVisible()
}

export async function expectAuthError(page: Page, errorMessage: string) {
  await expect(page.locator('[data-testid="error-message"]')).toContainText(errorMessage)
}

export async function expectValidationError(page: Page, field: string) {
  await expect(page.locator(`[data-testid="${field}-error"]`)).toBeVisible()
}

export async function switchTenant(page: Page, tenantName: string) {
  // Öffne Tenant Switcher
  await page.click('[data-testid="tenant-switcher"]')
  
  // Wähle Tenant
  await page.click(`[data-testid="tenant-option-${tenantName}"]`)
  
  // Warte auf Tenant-Wechsel
  await page.waitForTimeout(500)
  
  // Verifiziere Tenant-Wechsel
  await expect(page.locator('[data-testid="current-tenant"]')).toContainText(tenantName)
}