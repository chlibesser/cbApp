# Domain-spezifische Test-Struktur für cbApp V1

## 🎯 Domain-Isolation in Tests

Alle Tests müssen der Domain-Struktur folgen und Domain-Grenzen respektieren.

## 🏗️ Backend Test-Struktur

```
backend/tests/
├── Unit/
│   ├── Domains/
│   │   ├── Identity/
│   │   │   ├── Models/
│   │   │   │   ├── ProfileTest.php
│   │   │   │   └── RoleTest.php
│   │   │   ├── Services/
│   │   │   │   └── ProfileServiceTest.php
│   │   │   └── Repositories/
│   │   │       └── ProfileRepositoryTest.php
│   │   ├── Tenant/
│   │   │   ├── Models/
│   │   │   │   ├── TenantTest.php
│   │   │   │   └── RoleTest.php
│   │   │   ├── Services/
│   │   │   │   └── TenantServiceTest.php
│   │   │   └── Middleware/
│   │   │       └── TenantScopeTest.php
│   │   └── Admin/
│   │       └── Controllers/
│   │           ├── AccountControllerTest.php
│   │           └── TenantControllerTest.php
│   └── Core/
│       ├── Auth/
│       │   ├── Models/
│       │   │   ├── AccountTest.php      # ✅ Vorhanden
│       │   │   └── SessionTest.php
│       │   ├── Services/
│       │   │   └── AuthServiceTest.php
│       │   └── Enums/
│       │       └── SystemRoleTest.php
│       └── Shared/
│           ├── Models/
│           │   ├── BaseModelTest.php
│           │   └── PermissionTest.php
│           └── Enums/
│               └── SystemRoleTest.php
├── Feature/
│   ├── Domains/
│   │   ├── Identity/
│   │   │   └── Controllers/
│   │   │       └── ProfileControllerTest.php
│   │   ├── Tenant/
│   │   │   └── Controllers/
│   │   │       └── TenantControllerTest.php
│   │   └── Admin/
│   │       └── Controllers/
│   │           ├── AccountControllerTest.php
│   │           └── TenantControllerTest.php
│   └── Core/
│       └── Auth/
│           └── Controllers/
│               ├── LoginControllerTest.php    # ✅ Vorhanden
│               └── QuickLoginControllerTest.php
└── Integration/
    ├── Auth/
    │   ├── SanctumIntegrationTest.php
    │   └── TenantAuthTest.php
    └── Domains/
        ├── Identity/
        ├── Tenant/
        └── Admin/
```

## 🎨 Frontend Test-Struktur

```
frontend/tests/
├── unit/
│   ├── domains/
│   │   ├── identity/
│   │   │   ├── views/
│   │   │   │   └── LoginView.test.ts
│   │   │   ├── services/
│   │   │   │   └── profileService.test.ts
│   │   │   └── stores/
│   │   │       └── profileStore.test.ts
│   │   ├── tenant/
│   │   │   ├── views/
│   │   │   │   └── TenantView.test.ts
│   │   │   ├── services/
│   │   │   │   └── tenantService.test.ts
│   │   │   └── stores/
│   │   │       └── tenantStore.test.ts
│   │   └── admin/
│   │       ├── views/
│   │       │   ├── AccountsView.test.ts
│   │       │   └── TenantsView.test.ts
│   │       └── services/
│   │           ├── accountService.test.ts
│   │           └── tenantService.test.ts
│   ├── core/
│   │   ├── auth/
│   │   │   ├── authService.test.ts
│   │   │   └── types.test.ts
│   │   ├── api/
│   │   │   ├── apiClient.test.ts
│   │   │   └── types.test.ts
│   │   └── router/
│   │       ├── guards.test.ts
│   │       └── index.test.ts
│   ├── infrastructure/
│   │   ├── stores/
│   │   │   ├── authStore.test.ts
│   │   │   ├── layoutStore.test.ts
│   │   │   └── tenantStore.test.ts
│   │   └── plugins/
│   │       ├── pinia.test.ts
│   │       └── vuetify.test.ts
│   └── shared/
│       ├── components/
│       │   ├── AppHeader.test.ts           # ✅ Vorhanden
│       │   ├── AppSidebar.test.ts
│       │   ├── QuickLoginHelper.test.ts
│       │   └── ToastNotifications.test.ts
│       ├── layouts/
│       │   ├── AuthLayout.test.ts
│       │   └── DashboardLayout.test.ts
│       └── composables/
│           └── useApi.test.ts
├── integration/
│   ├── domains/
│   │   ├── identity/
│   │   │   └── authFlow.test.ts
│   │   ├── tenant/
│   │   │   └── tenantFlow.test.ts
│   │   └── admin/
│   │       └── adminFlow.test.ts
│   └── core/
│       ├── auth/
│       └── api/
└── e2e/                                    # ✅ Vorhanden
    ├── auth.spec.ts
    ├── quickLogin.spec.ts
    ├── tenant.spec.ts
    └── helpers/
```

## 🛡️ Domain-Test Regeln

### ✅ **Erlaubt in Domain-Tests:**
- Tests für eigene Domain-Komponenten
- Mocking von anderen Domains
- Tests für Domain-interne Integration
- Shared/Core Components testen

### ❌ **Verboten in Domain-Tests:**
- Direkte Tests von anderen Domains
- Cross-Domain Integration ohne Mocking
- Änderungen an fremden Domain-Models

### 📝 **Beispiel: Identity Domain Test**
```php
// ✅ RICHTIG - Nur Identity Domain
class ProfileServiceTest extends TestCase
{
    public function test_creates_profile_for_tenant()
    {
        // Mock Tenant (andere Domain)
        $tenant = Tenant::factory()->create();
        
        // Test eigene Domain
        $profile = $this->profileService->create($tenant, $data);
        
        // Assert nur eigene Models
        $this->assertInstanceOf(Profile::class, $profile);
    }
}

// ❌ FALSCH - Cross-Domain Test
class ProfileServiceTest extends TestCase
{
    public function test_creates_tenant_and_profile()  // ❌ Testet zwei Domains
    {
        $tenant = $this->tenantService->create();      // ❌ Andere Domain
        $profile = $this->profileService->create();    // ✅ Eigene Domain
    }
}
```

## 🎯 Domain-spezifische Test-Prioritäten

### 🔴 **Kritisch - Identity Domain**
1. ProfileService Tests
2. Profile Model Tests  
3. ProfileController Tests
4. LoginView Component Tests

### 🔴 **Kritisch - Auth Domain**
1. AuthService Tests
2. Account Model Tests (vervollständigen)
3. LoginController Tests (vervollständigen)
4. SystemRole Enum Tests

### 🟡 **Wichtig - Tenant Domain**
1. TenantService Tests
2. Tenant Model Tests
3. TenantScope Middleware Tests
4. Tenant Store Tests

### 🟡 **Wichtig - Admin Domain**
1. AccountController Tests
2. TenantController Tests
3. Admin Views Tests
4. Admin Service Tests

### 🟢 **Normal - Shared/Core**
1. BaseModel Tests
2. Permission Tests
3. Shared Component Tests
4. Core Service Tests

---

**Nächster Schritt:** Beginne mit Identity Domain Tests und arbeite domain-weise durch alle Tests.