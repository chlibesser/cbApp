# E2E Testing - Automatische Klick-Tests für cbApp V1

## 🎯 Was sind automatische Klick-Tests?

**E2E (End-to-End) Tests** simulieren echte Benutzerinteraktionen im Browser:
- Automatische Klicks auf Buttons
- Eingabe in Formulare  
- Navigation zwischen Seiten
- Validierung von UI-Elementen
- Screenshot-Vergleiche

## 🛠️ Tool-Optionen für cbApp V1

### 1. **Playwright** (⭐ EMPFOHLEN)
```bash
# Installation
pnpm add -D @playwright/test

# Konfiguration: playwright.config.ts
# Tests: tests/e2e/*.spec.ts
```

**Vorteile:**
- ✅ Sehr schnell und stabil
- ✅ Multi-Browser (Chrome, Firefox, Safari)
- ✅ Excellente TypeScript-Unterstützung
- ✅ Auto-Wait für Elemente
- ✅ Screenshots + Video-Recording
- ✅ Von Microsoft entwickelt

### 2. **Cypress** (Alternative)
```bash
# Installation  
pnpm add -D cypress

# Konfiguration: cypress.config.ts
# Tests: cypress/e2e/*.cy.ts
```

**Vorteile:**
- ✅ Sehr benutzerfreundlich
- ✅ Real-Time Browser Preview
- ✅ Zeit-Reise Debugging
- ✅ Große Community

**Nachteile:**
- ❌ Langsamer als Playwright
- ❌ Nur Chromium-basierte Browser

## 🎬 Beispiel: Login E2E Test

### Playwright Test
```typescript
// tests/e2e/auth.spec.ts
import { test, expect } from '@playwright/test'

test.describe('Authentication Flow', () => {
  test('should login successfully with valid credentials', async ({ page }) => {
    // Navigate to login page
    await page.goto('/auth/login')
    
    // Fill login form
    await page.fill('[data-testid="login-identifier"]', 'admin@cbapp.ch')
    await page.fill('[data-testid="login-password"]', 'password123')
    
    // Click login button
    await page.click('[data-testid="login-submit"]')
    
    // Wait for redirect to dashboard
    await page.waitForURL('/dashboard')
    
    // Verify user is logged in
    await expect(page.locator('[data-testid="user-menu"]')).toBeVisible()
    await expect(page.locator('text=Dashboard')).toBeVisible()
  })

  test('should show error for invalid credentials', async ({ page }) => {
    await page.goto('/auth/login')
    
    await page.fill('[data-testid="login-identifier"]', 'wrong@email.com')
    await page.fill('[data-testid="login-password"]', 'wrongpassword')
    await page.click('[data-testid="login-submit"]')
    
    // Verify error message appears
    await expect(page.locator('[data-testid="error-message"]')).toContainText('Anmeldedaten sind ungültig')
  })

  test('should validate required fields', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Try to submit empty form
    await page.click('[data-testid="login-submit"]')
    
    // Verify validation errors
    await expect(page.locator('[data-testid="identifier-error"]')).toBeVisible()
    await expect(page.locator('[data-testid="password-error"]')).toBeVisible()
  })
})
```

## 🚀 Quick Login E2E Tests

```typescript
// tests/e2e/quickLogin.spec.ts
test.describe('Quick Login (Development)', () => {
  test('should show quick login helper in development', async ({ page }) => {
    // Set development environment
    await page.addInitScript(() => {
      window.localStorage.setItem('ENV', 'development')
    })
    
    await page.goto('/auth/login')
    
    // Verify Quick Login Helper is visible
    await expect(page.locator('[data-testid="quick-login-helper"]')).toBeVisible()
  })

  test('should perform quick login with admin account', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Click on Admin quick login
    await page.click('[data-testid="quick-login-admin"]')
    
    // Should be redirected to dashboard
    await page.waitForURL('/dashboard')
    await expect(page.locator('[data-testid="user-menu"]')).toContainText('admin@cbapp.ch')
  })

  test('should perform quick login with tenant selection', async ({ page }) => {
    await page.goto('/auth/login')
    
    // Click on account with multiple tenants
    await page.click('[data-testid="quick-login-user-with-tenants"]')
    
    // Select tenant from dropdown
    await page.selectOption('[data-testid="tenant-selector"]', 'tenant-1')
    await page.click('[data-testid="confirm-tenant"]')
    
    // Verify correct tenant context
    await page.waitForURL('/dashboard')
    await expect(page.locator('[data-testid="current-tenant"]')).toContainText('Test Firma GmbH')
  })
})
```

## 🏢 Multi-Tenant E2E Tests

```typescript
// tests/e2e/tenant.spec.ts
test.describe('Multi-Tenant Workflows', () => {
  test('should switch between tenants', async ({ page }) => {
    // Login as user with multiple tenants
    await page.goto('/auth/login')
    await page.fill('[data-testid="login-identifier"]', 'multiuser@cbapp.ch')
    await page.fill('[data-testid="login-password"]', 'password123')
    await page.click('[data-testid="login-submit"]')
    
    await page.waitForURL('/dashboard')
    
    // Open tenant switcher
    await page.click('[data-testid="tenant-switcher"]')
    
    // Switch to different tenant
    await page.click('[data-testid="tenant-option-2"]')
    
    // Verify tenant context changed
    await expect(page.locator('[data-testid="current-tenant"]')).toContainText('Andere Firma AG')
    await expect(page.url()).toContain('tenant=2')
  })

  test('should show tenant-specific permissions', async ({ page }) => {
    // Login as tenant admin
    await page.goto('/auth/login')
    await page.fill('[data-testid="login-identifier"]', 'tenantadmin@cbapp.ch')
    await page.fill('[data-testid="login-password"]', 'password123')
    await page.click('[data-testid="login-submit"]')
    
    await page.waitForURL('/dashboard')
    
    // Verify admin menu is visible
    await expect(page.locator('[data-testid="admin-menu"]')).toBeVisible()
    await expect(page.locator('text=Benutzer verwalten')).toBeVisible()
    
    // Switch to regular user role
    await page.click('[data-testid="role-switcher"]')
    await page.selectOption('[data-testid="role-selector"]', 'member')
    
    // Verify admin menu is hidden
    await expect(page.locator('[data-testid="admin-menu"]')).not.toBeVisible()
  })
})
```

## 🎯 Test Data Management für E2E

### Test-Datenbank Setup
```typescript
// tests/e2e/setup.ts
import { test as setup } from '@playwright/test'

setup('prepare test database', async ({ page }) => {
  // Reset database to clean state
  await page.request.post('/api/testing/reset-database')
  
  // Seed test data
  await page.request.post('/api/testing/seed-test-data')
})

// playwright.config.ts
export default {
  projects: [
    {
      name: 'setup',
      testMatch: /.*\.setup\.ts/,
    },
    {
      name: 'e2e tests',
      dependencies: ['setup'],
      testMatch: /.*\.spec\.ts/,
    }
  ]
}
```

### Test-User Management
```typescript
// tests/e2e/helpers/testUsers.ts
export const testUsers = {
  globalAdmin: {
    identifier: 'globaladmin@cbapp.ch',
    password: 'password123',
    role: 'global_admin'
  },
  tenantAdmin: {
    identifier: 'tenantadmin@cbapp.ch', 
    password: 'password123',
    tenant: 'test-firma-gmbh',
    role: 'admin'
  },
  regularUser: {
    identifier: 'user@cbapp.ch',
    password: 'password123',
    tenant: 'test-firma-gmbh',
    role: 'member'
  },
  multiTenantUser: {
    identifier: 'multiuser@cbapp.ch',
    password: 'password123',
    tenants: ['test-firma-gmbh', 'andere-firma-ag']
  }
}
```

## 📸 Visual Testing

### Screenshot Tests
```typescript
test('should match login page design', async ({ page }) => {
  await page.goto('/auth/login')
  
  // Full page screenshot
  await expect(page).toHaveScreenshot('login-page.png')
  
  // Specific component screenshot
  await expect(page.locator('[data-testid="login-form"]')).toHaveScreenshot('login-form.png')
})

test('should match dashboard layout', async ({ page }) => {
  // Login first
  await loginAsUser(page, testUsers.regularUser)
  
  await page.waitForURL('/dashboard')
  
  // Screenshot with different viewport sizes
  await page.setViewportSize({ width: 1920, height: 1080 })
  await expect(page).toHaveScreenshot('dashboard-desktop.png')
  
  await page.setViewportSize({ width: 375, height: 667 })
  await expect(page).toHaveScreenshot('dashboard-mobile.png')
})
```

## ⚡ Performance Testing

```typescript
// tests/e2e/performance.spec.ts
test('should load login page quickly', async ({ page }) => {
  const startTime = Date.now()
  
  await page.goto('/auth/login')
  await page.waitForLoadState('networkidle')
  
  const loadTime = Date.now() - startTime
  expect(loadTime).toBeLessThan(2000) // Under 2 seconds
})

test('should handle quick login performance', async ({ page }) => {
  await page.goto('/auth/login')
  
  const startTime = Date.now()
  await page.click('[data-testid="quick-login-admin"]')
  await page.waitForURL('/dashboard')
  
  const loginTime = Date.now() - startTime
  expect(loginTime).toBeLessThan(1000) // Under 1 second
})
```

## 🎬 Test Recording & Debugging

### Video Recording
```typescript
// playwright.config.ts
export default {
  use: {
    video: 'on-first-retry', // Record video on failures
    screenshot: 'only-on-failure',
    trace: 'on-first-retry' // Detailed trace for debugging
  }
}
```

### Debug Mode
```bash
# Run single test with browser visible
pnpm playwright test tests/e2e/auth.spec.ts --headed --debug

# Generate test code by recording actions
pnpm playwright codegen http://localhost:5173
```

## 🚀 Ausführung der E2E Tests

### Lokale Entwicklung
```bash
# Install Playwright
pnpm playwright install

# Run all E2E tests
pnpm test:e2e

# Run specific test file
pnpm playwright test tests/e2e/auth.spec.ts

# Run tests in headed mode (browser visible)
pnpm playwright test --headed

# Generate HTML report
pnpm playwright show-report
```

### CI/CD Integration
```bash
# Headless run for CI
pnpm playwright test --reporter=html

# Parallel execution
pnpm playwright test --workers=4

# Cross-browser testing
pnpm playwright test --project=chromium --project=firefox
```

## 📋 E2E Test-Struktur

```
tests/e2e/
├── auth.spec.ts           # Login/Logout Tests
├── quickLogin.spec.ts     # Quick Login Tests  
├── tenant.spec.ts         # Multi-Tenant Tests
├── navigation.spec.ts     # App Navigation
├── forms.spec.ts          # Form Validation
├── permissions.spec.ts    # Role-based Access
├── visual.spec.ts         # Screenshot Tests
├── performance.spec.ts    # Performance Tests
├── helpers/
│   ├── testUsers.ts       # Test User Data
│   ├── loginHelpers.ts    # Login Helper Functions
│   └── assertions.ts      # Custom Assertions
└── setup/
    └── database.setup.ts  # Test Database Setup
```

## 🎯 E2E Test Coverage

### Kritische User Journeys
- ✅ **Login Flow** - Email/Username + Password
- ✅ **Quick Login** - Development Helper  
- ✅ **Logout Flow** - Token Cleanup
- ✅ **Multi-Tenant** - Tenant Switching
- ✅ **Permissions** - Role-based UI
- ✅ **Form Validation** - Error Handling
- ✅ **Navigation** - Route Guards

### Visual Regression
- ✅ **Login Page** - Design Consistency
- ✅ **Dashboard** - Layout Responsiveness
- ✅ **Modals** - Dialog Appearance
- ✅ **Error States** - Message Display

**Automatische Klick-Tests sind essential für die Qualitätssicherung!** 🎯