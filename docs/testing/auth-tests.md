# Auth-System Testing - cbApp V1

## 🎯 Übersicht

Komplette Test-Abdeckung für das cbApp V1 Authentication-System inklusive Account-Management, Profile-System, Quick Login, System Roles und Multi-Tenant Authentication.

## 📋 Test-Status Dashboard

### Backend Tests Status

| Kategorie | Implementiert | Fehlend | Priorität |
|-----------|---------------|---------|-----------|
| **Models** | 7/35 (20%) | 28 | 🔴 Hoch |
| **Services** | 0/15 (0%) | 15 | 🔴 Kritisch |
| **API Endpoints** | 7/35 (20%) | 28 | 🔴 Hoch |
| **Middleware/Guards** | 0/8 (0%) | 8 | 🟡 Medium |
| **Integration** | 0/12 (0%) | 12 | 🟡 Medium |

### Frontend Tests Status

| Kategorie | Implementiert | Fehlend | Priorität |
|-----------|---------------|---------|-----------|
| **Stores** | 0/25 (0%) | 25 | 🔴 Kritisch |
| **Services** | 0/15 (0%) | 15 | 🔴 Hoch |
| **Components** | 1/20 (5%) | 19 | 🔴 Hoch |
| **Views** | 0/8 (0%) | 8 | 🔴 Hoch |
| **Integration** | 0/20 (0%) | 20 | 🟡 Medium |
| **E2E** | 0/8 (0%) | 8 | 🟢 Niedrig |

## 🏗️ Backend Auth Tests

### 1. Model Tests (Unit)

#### ✅ Account Model (`tests/Unit/Models/AccountTest.php`)
**Status: 7/15 Tests implementiert**

**Implementiert:**
- `it_creates_account_with_valid_data()`
- `it_hashes_password_automatically()`
- `it_casts_system_role_to_enum()`
- `it_allows_null_system_role_for_regular_accounts()`
- `it_validates_email_uniqueness()`
- `it_has_tenants_relationship()`
- `it_checks_if_account_is_global_admin()`

**🔴 KRITISCH - Fehlend:**
```php
// Username & Login
it_validates_username_uniqueness()
it_finds_account_by_username_or_email()
it_checks_if_account_is_active()

// Relationships
it_has_profiles_relationship()
it_gets_role_for_specific_tenant()

// Permissions
it_checks_tenant_permissions()
it_gets_all_permissions_for_tenant()
it_has_enhanced_permission_check()

// System Roles
it_checks_system_role_helper_methods() // isAdmin, isTenantAdmin
it_assigns_system_role()

// API Tokens
it_creates_and_manages_api_tokens()
```

#### ❌ Profile Model (`tests/Unit/Models/ProfileTest.php`)
**Status: KOMPLETT FEHLEND**

```php
it_creates_profile_with_valid_data()
it_belongs_to_account()
it_belongs_to_tenant()
it_gets_role_through_account_tenant_relationship()
it_generates_full_name_attribute()
it_handles_preferences_as_array()
it_validates_required_fields()
```

#### ❌ SystemRole Enum (`tests/Unit/Enums/SystemRoleTest.php`)
**Status: KOMPLETT FEHLEND**

```php
it_provides_correct_labels()
it_provides_correct_descriptions()
it_checks_tenant_management_permissions()
it_checks_admin_status()
it_gets_global_permissions()
it_checks_specific_global_permissions()
```

### 2. Service Tests (Unit)

#### ❌ AuthService (`tests/Unit/Services/AuthServiceTest.php`)
**Status: KOMPLETT FEHLEND - 🔴 KRITISCH**

```php
it_attempts_authentication_with_username()
it_attempts_authentication_with_email()
it_rejects_invalid_credentials()
it_rejects_inactive_accounts()
it_creates_api_token()
it_revokes_all_tokens_on_logout()
it_registers_new_account()
it_hashes_password_during_registration()
```

#### ❌ ProfileService (`tests/Unit/Services/ProfileServiceTest.php`)
**Status: KOMPLETT FEHLEND**

```php
it_creates_profile_for_account_and_tenant()
it_gets_all_account_profiles()
it_switches_profile_for_valid_account()
it_rejects_profile_switch_for_different_account()
it_stores_current_profile_in_session()
```

### 3. API Tests (Feature)

#### ✅ LoginController (`tests/Feature/Auth/LoginTest.php`)
**Status: 7/12 Tests implementiert**

**🔴 KRITISCH - Fehlend:**
```php
it_logs_in_with_username_instead_of_email()
it_rejects_inactive_accounts()
it_includes_system_role_in_login_response()
it_handles_missing_authorization_header_gracefully()
it_handles_invalid_token_gracefully()
```

#### ❌ QuickLoginController (`tests/Feature/Auth/QuickLoginTest.php`)
**Status: KOMPLETT FEHLEND - 🔴 KRITISCH**

```php
it_lists_available_quick_login_accounts()
it_includes_tenant_information_in_account_list()
it_includes_system_role_information()
it_performs_quick_login_with_username()
it_performs_quick_login_with_tenant_selection()
it_creates_quick_login_token()
it_rejects_quick_login_in_production()
```

#### ❌ ProfileController (`tests/Feature/Auth/ProfileTest.php`)
**Status: KOMPLETT FEHLEND**

```php
it_gets_account_profiles_for_authenticated_user()
it_switches_to_valid_profile()
it_rejects_profile_switch_to_invalid_profile()
it_returns_current_profile()
it_requires_authentication_for_profile_endpoints()
```

## 🎨 Frontend Auth Tests

### 1. Store Tests (Unit)

#### ❌ AuthStore (`tests/unit/stores/authStore.test.ts`)
**Status: KOMPLETT FEHLEND - 🔴 KRITISCH**

```typescript
it('initializes with stored token')
it('handles login success')
it('handles login failure')
it('clears auth state on logout')
it('loads user data with valid token')
it('clears auth on failed user data load')
it('loads quick login accounts')
it('performs quick login')
it('handles quick login with tenant')
it('checks permissions')
it('checks resource access')
```

#### ❌ TenantStore (`tests/unit/stores/tenantStore.test.ts`)
**Status: KOMPLETT FEHLEND**

```typescript
it('loads tenants list')
it('creates new tenant')
it('sets current tenant')
it('handles loading states')
it('handles error states')
```

### 2. Service Tests (Unit)

#### ❌ AuthService (`tests/unit/services/authService.test.ts`)
**Status: KOMPLETT FEHLEND - 🔴 KRITISCH**

```typescript
it('performs login with credentials')
it('stores token after login')
it('performs logout')
it('removes token after logout')
it('gets current user data')
it('manages token storage')
it('gets quick login accounts')
it('performs quick login')
it('handles api errors')
```

### 3. Component Tests (Unit)

#### ❌ LoginView (`tests/unit/views/LoginView.test.ts`)
**Status: KOMPLETT FEHLEND - 🔴 KRITISCH**

```typescript
it('renders login form')
it('validates identifier field')
it('validates password field')
it('submits form with valid data')
it('prevents submit with invalid data')
it('shows loading state during submission')
it('displays error messages')
it('redirects after successful login')
it('renders quick login helper in dev')
```

#### ❌ QuickLoginHelper (`tests/unit/components/QuickLoginHelper.test.ts`)
**Status: KOMPLETT FEHLEND**

```typescript
it('renders only in development')
it('loads quick login accounts on mount')
it('displays account list')
it('displays system role information')
it('performs quick login on click')
it('handles quick login with tenant')
it('shows loading state')
it('redirects after successful login')
```

### 4. Integration Tests

#### ❌ Complete Auth Flows
**Status: KOMPLETT FEHLEND**

```typescript
// Login Flow
it('completes full login flow')
it('handles login with username')
it('persists authentication across page refresh')
it('handles token expiry')

// Quick Login Flow  
it('completes quick login flow')
it('performs quick login without tenant')
it('performs quick login with tenant')

// Profile Management
it('loads user profiles')
it('switches between profiles')
it('maintains profile state')

// Multi-Tenant
it('switches between tenants')
it('checks tenant specific permissions')
```

## ⚡ Sofortige Prioritäten

### 🔴 **KRITISCH - Diese Woche**

1. **AuthService (Backend)** - Core Authentication Logic
2. **LoginController (Backend)** - Fehlende API-Tests
3. **AuthStore (Frontend)** - State Management für Auth
4. **LoginView (Frontend)** - UI Component Testing

### 🟡 **WICHTIG - Nächste Woche**

5. **QuickLoginController (Backend)** - Development Helper
6. **ProfileService (Backend/Frontend)** - Profile Management
7. **SystemRole Enum Tests** - Permission System
8. **QuickLoginHelper (Frontend)** - UI Component

### 🟢 **NORMAL - Später**

9. **E2E Tests** - Browser-basierte Tests
10. **Integration Tests** - Cross-Component Testing
11. **Edge Cases** - Error Handling
12. **Performance Tests** - Load Testing

## 🛠️ Test-Implementierung Guide

### Backend Test-Template

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Core\Auth\Services\AuthService;
use App\Core\Auth\Models\Account;

class AuthServiceTest extends TestCase
{
    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = app(AuthService::class);
    }

    /** @test */
    public function it_attempts_authentication_with_email(): void
    {
        $account = Account::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $result = $this->authService->attempt([
            'identifier' => 'test@example.com',
            'password' => 'password123'
        ]);

        $this->assertTrue($result);
        $this->assertEquals($account->id, auth()->id());
    }
}
```

### Frontend Test-Template

```typescript
import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/infrastructure/stores/authStore'

describe('AuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('handles login success', async () => {
    const authStore = useAuthStore()
    
    // Mock API response
    vi.mocked(authService.login).mockResolvedValue({
      token: 'mock-token',
      account: { id: '1', email: 'test@example.com' }
    })

    await authStore.login('test@example.com', 'password')

    expect(authStore.isAuthenticated).toBe(true)
    expect(authStore.token).toBe('mock-token')
    expect(authStore.account.email).toBe('test@example.com')
  })
})
```

## 📊 Test-Coverage Ziele

| Bereich | Aktuell | Ziel | Kritisch |
|---------|---------|------|----------|
| **Backend Models** | 20% | 95% | ✅ |
| **Backend Services** | 0% | 90% | ✅ |
| **Backend API** | 20% | 85% | ✅ |
| **Frontend Stores** | 0% | 90% | ✅ |
| **Frontend Services** | 0% | 85% | ✅ |
| **Frontend Components** | 5% | 80% | ✅ |

## 🚀 Ausführungs-Commands

### Backend Tests
```bash
# Alle Auth-Tests
./vendor/bin/phpunit tests/Unit/Models/AccountTest.php
./vendor/bin/phpunit tests/Feature/Auth/

# Spezifische Tests  
./vendor/bin/phpunit tests/Unit/Services/AuthServiceTest.php
./vendor/bin/phpunit tests/Feature/Auth/QuickLoginTest.php
```

### Frontend Tests
```bash
# Alle Auth-Tests
pnpm test tests/unit/stores/authStore.test.ts
pnpm test tests/unit/views/LoginView.test.ts

# Watch Mode für Development
pnpm test:watch tests/unit/stores/
```

---

**Nächste Schritte**: Priorisierung der KRITISCHEN Tests und schrittweise Implementierung beginnend mit den Backend Services und Frontend Stores.