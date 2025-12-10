import { test, expect } from '@playwright/test'
import { testUsers } from './helpers/testUsers'
import { 
  loginAsUser, 
  logout, 
  expectToBeOnLoginPage, 
  expectToBeOnDashboard,
  expectAuthError,
  expectValidationError
} from './helpers/loginHelpers'

test.describe('Authentication Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Stelle sicher dass wir nicht eingeloggt sind
    await page.goto('/auth/login')
  })

  test('should render login page correctly', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Verifiziere Login-Page Elements
    await expect(page.locator('[data-testid="login-form"]')).toBeVisible()
    await expect(page.locator('[data-testid="login-identifier"]')).toBeVisible()
    await expect(page.locator('[data-testid="login-password"]')).toBeVisible()
    await expect(page.locator('[data-testid="login-submit"]')).toBeVisible()
    
    // Verifiziere Page Title und Text
    await expect(page).toHaveTitle(/cbApp V1/)
    await expect(page.locator('text=Login')).toBeVisible()
  })

  test('should login successfully with valid email', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Login mit Email
    await page.fill('[data-testid="login-identifier"]', testUsers.regularUser.identifier)
    await page.fill('[data-testid="login-password"]', testUsers.regularUser.password)
    await page.click('[data-testid="login-submit"]')
    
    // Verifiziere erfolgreichen Login
    await expectToBeOnDashboard(page)
    await expect(page.locator('[data-testid="user-menu"]')).toBeVisible()
    
    // Verifiziere User-Informationen im UI
    await page.click('[data-testid="user-menu"]')
    await expect(page.locator('[data-testid="user-email"]')).toContainText(testUsers.regularUser.identifier)
  })

  test('should login successfully with username', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Login mit Username statt Email
    await page.fill('[data-testid="login-identifier"]', 'testuser')
    await page.fill('[data-testid="login-password"]', 'password123')
    await page.click('[data-testid="login-submit"]')
    
    // Verifiziere erfolgreichen Login
    await expectToBeOnDashboard(page)
    await expect(page.locator('[data-testid="user-menu"]')).toBeVisible()
  })

  test('should show error for invalid credentials', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Versuche Login mit falschen Daten
    await page.fill('[data-testid="login-identifier"]', 'wrong@email.com')
    await page.fill('[data-testid="login-password"]', 'wrongpassword')
    await page.click('[data-testid="login-submit"]')
    
    // Verifiziere Error-Message
    await expectAuthError(page, 'Die Anmeldedaten sind ungültig')
    
    // Verifiziere dass wir auf Login-Page bleiben
    await expectToBeOnLoginPage(page)
  })

  test('should validate required fields', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Versuche Submit ohne Daten
    await page.click('[data-testid="login-submit"]')
    
    // Verifiziere Validation Errors
    await expectValidationError(page, 'identifier')
    await expectValidationError(page, 'password')
    
    // Fülle nur Email aus
    await page.fill('[data-testid="login-identifier"]', 'test@example.com')
    await page.click('[data-testid="login-submit"]')
    
    // Password sollte noch fehlerhaft sein
    await expectValidationError(page, 'password')
  })

  test('should validate email format', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Ungültiges Email-Format
    await page.fill('[data-testid="login-identifier"]', 'invalid-email')
    await page.fill('[data-testid="login-password"]', 'password123')
    await page.click('[data-testid="login-submit"]')
    
    // Sollte Validation Error zeigen
    await expectValidationError(page, 'identifier')
  })

  test('should show loading state during login', async ({ page }) => {
    await page.goto('/auth/login')
    
    await page.fill('[data-testid="login-identifier"]', testUsers.regularUser.identifier)
    await page.fill('[data-testid="login-password"]', testUsers.regularUser.password)
    
    // Klicke Submit und prüfe Loading-State
    await page.click('[data-testid="login-submit"]')
    
    // Login-Button sollte Loading-State zeigen
    await expect(page.locator('[data-testid="login-submit"]')).toBeDisabled()
    await expect(page.locator('[data-testid="login-loading"]')).toBeVisible()
  })

  test('should logout successfully', async ({ page }) => {
    // Login first
    await loginAsUser(page, testUsers.regularUser)
    await expectToBeOnDashboard(page)
    
    // Logout
    await logout(page)
    
    // Verifiziere dass wir ausgeloggt sind
    await expectToBeOnLoginPage(page)
    
    // Versuche Dashboard zu erreichen (sollte redirecten)
    await page.goto('/dashboard')
    await expectToBeOnLoginPage(page)
  })

  test('should persist authentication across page refresh', async ({ page }) => {
    // Login
    await loginAsUser(page, testUsers.regularUser)
    await expectToBeOnDashboard(page)
    
    // Page Refresh
    await page.reload()
    
    // Sollte noch eingeloggt sein
    await expectToBeOnDashboard(page)
    await expect(page.locator('[data-testid="user-menu"]')).toBeVisible()
  })

  test('should redirect unauthenticated users from protected routes', async ({ page }) => {
    // Versuche direkt auf geschützte Route zuzugreifen
    await page.goto('/dashboard')
    
    // Sollte zu Login redirecten
    await expectToBeOnLoginPage(page)
    
    // Admin-Routes
    await page.goto('/admin/accounts')
    await expectToBeOnLoginPage(page)
    
    await page.goto('/admin/tenants')
    await expectToBeOnLoginPage(page)
  })

  test('should handle expired token gracefully', async ({ page }) => {
    // Login first
    await loginAsUser(page, testUsers.regularUser)
    await expectToBeOnDashboard(page)
    
    // Simuliere expired token durch localStorage clear
    await page.evaluate(() => {
      localStorage.removeItem('auth-token')
    })
    
    // Versuche API-Call (sollte 401 returnen)
    await page.reload()
    
    // Sollte zu Login redirecten
    await expectToBeOnLoginPage(page)
  })

  test('should display system role for global admin', async ({ page }) => {
    await loginAsUser(page, testUsers.globalAdmin)
    await expectToBeOnDashboard(page)
    
    // Öffne User Menu
    await page.click('[data-testid="user-menu"]')
    
    // Verifiziere System Role wird angezeigt
    await expect(page.locator('[data-testid="user-role"]')).toContainText('Global Administrator')
  })

  test('should show admin navigation for global admin', async ({ page }) => {
    await loginAsUser(page, testUsers.globalAdmin)
    await expectToBeOnDashboard(page)
    
    // Global Admin sollte Admin-Menu sehen
    await expect(page.locator('[data-testid="admin-menu"]')).toBeVisible()
    await expect(page.locator('[data-testid="admin-accounts"]')).toBeVisible()
    await expect(page.locator('[data-testid="admin-tenants"]')).toBeVisible()
  })

  test('should hide admin navigation for regular users', async ({ page }) => {
    await loginAsUser(page, testUsers.regularUser)
    await expectToBeOnDashboard(page)
    
    // Regular User sollte kein Admin-Menu sehen
    await expect(page.locator('[data-testid="admin-menu"]')).not.toBeVisible()
  })
})