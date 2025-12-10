# Testing-Konzept für cbApp V1

## 🎯 Übersicht

Umfassendes Testing-Konzept für cbApp V1 mit vollständiger Backend- und Frontend-Test-Abdeckung.

## 🏗️ Testing-Architektur

### Backend Testing (Laravel/PHPUnit)
```
backend/tests/
├── Feature/           # Integration & API Tests
│   ├── Auth/          # Authentication Tests
│   ├── Admin/         # Admin-Funktionen Tests
│   ├── Tenant/        # Tenant-Management Tests
│   └── Identity/      # Profile-Management Tests
├── Unit/              # Unit Tests
│   ├── Models/        # Model Tests
│   ├── Services/      # Service Tests
│   └── Enums/         # Enum Tests
└── Helpers/           # Test-Hilfsfunktionen
```

### Frontend Testing (Vue/Vitest)
```
frontend/tests/
├── unit/              # Component Unit Tests
│   ├── components/    # Shared Components
│   ├── domains/       # Domain-spezifische Tests
│   └── stores/        # Pinia Store Tests
├── integration/       # Integration Tests
│   ├── auth/          # Auth-Flow Tests
│   ├── api/           # API-Integration Tests
│   └── routing/       # Router Tests
└── e2e/              # End-to-End Tests (optional)
```

## 🚀 Test-Ausführung

### Backend Tests
```bash
# Alle Tests ausführen
./vendor/bin/phpunit

# Spezifische Test-Suite
./vendor/bin/phpunit --testsuite=Feature
./vendor/bin/phpunit --testsuite=Unit

# Mit Coverage
./vendor/bin/phpunit --coverage-html coverage/

# Einzelne Tests
./vendor/bin/phpunit tests/Feature/Auth/LoginTest.php
```

### Frontend Tests
```bash
# Alle Tests ausführen
pnpm test

# Watch-Modus
pnpm test:watch

# Coverage
pnpm test:coverage

# UI-Modus
pnpm test:ui
```

## 🔧 Backend Testing Setup

### Test-Datenbank Konfiguration
- **SQLite In-Memory** für schnelle Tests
- **Separate Test-Database** für umfangreiche Tests
- **Transaktionale Tests** für saubere Isolation

### Test-Kategorien

#### 1. Model Tests (Unit)
- **Validierung**: Attribute, Regeln, Casts
- **Relationships**: belongsTo, hasMany, morphTo
- **Scopes**: Query-Scopes, Global-Scopes
- **Mutators/Accessors**: Datenmanipulation

#### 2. Service Tests (Unit)
- **Business Logic**: Geschäftsregeln
- **Edge Cases**: Fehlerfälle, Validierung
- **Dependencies**: Mock/Stub externe Services

#### 3. API Tests (Feature)
- **Authentication**: Login, Logout, Token-Validation
- **Authorization**: Permissions, Roles, Tenant-Scope
- **CRUD Operations**: Create, Read, Update, Delete
- **Validation**: Request-Validation, Error-Responses
- **Response Format**: JSON-Struktur, Status-Codes

#### 4. Integration Tests (Feature)
- **Complete Workflows**: End-to-End Geschäftsprozesse
- **Database Transactions**: Multi-Table Operations
- **Event/Queue Testing**: Jobs, Events, Notifications

## 🎨 Frontend Testing Setup

### Test-Framework Stack
- **Vitest**: Test-Runner (Vite-integriert)
- **Vue Test Utils**: Vue-Component Testing
- **Testing Library**: User-centric Testing
- **Mock Service Worker (MSW)**: API-Mocking

### Test-Kategorien

#### 1. Component Tests (Unit)
- **Props/Emits**: Input/Output Testing
- **Slots**: Content-Projektion
- **Composition API**: Reactive State, Computed
- **User Interactions**: Click, Input, Form-Submission

#### 2. Store Tests (Unit)
- **Pinia Stores**: State, Actions, Getters
- **State Management**: Mutations, Persistence
- **API Integration**: Loading, Error-States

#### 3. Service Tests (Unit)
- **API Services**: HTTP-Calls, Error-Handling
- **Utilities**: Helper-Funktionen
- **Composables**: Vue-Komposition-Funktionen

#### 4. Integration Tests
- **Router Integration**: Navigation, Guards
- **API Integration**: Real HTTP-Calls (mocked)
- **User Flows**: Multi-Component Interactions

## 📊 Test-Coverage Ziele

### Backend Coverage
- **Models**: 95%+ (kritische Geschäftslogik)
- **Services**: 90%+ (Business Logic)
- **Controllers**: 85%+ (API-Endpoints)
- **Gesamt**: 85%+

### Frontend Coverage
- **Components**: 80%+ (UI-Komponenten)
- **Stores**: 90%+ (State Management)
- **Services**: 85%+ (API-Layer)
- **Gesamt**: 80%+

## 🛡️ Test-Sicherheit

### Datenbank-Isolation
- Jeder Test läuft in eigener Transaktion
- Automatisches Rollback nach Test
- Separate Test-Datenbank für Features

### Authentication Testing
- Mock-Authentication für Unit Tests
- Real-Authentication für Feature Tests
- Token-basierte API-Tests

### Tenant-Isolation Testing
- Multi-Tenant Scope-Testing
- Cross-Tenant Data-Leak Tests
- Permission-Boundary Tests

## 💻 Lokale Test-Ausführung (Hauptfokus)

### Alle Tests lokal ausführbar
- **Keine externen Dependencies** - alles läuft offline
- **SQLite In-Memory** - keine Datenbank-Setup nötig
- **Mock APIs** - keine externen Services
- **Sofortige Ausführung** - kein Warten auf CI/CD

### 🔄 Optional: Continuous Integration

GitHub Actions ist **komplett optional** für automatisierte Tests bei Commits.

```yaml
# Optional: .github/workflows/tests.yml
name: Tests (Optional)
on: [push, pull_request]
# ... Rest der Konfiguration
```

### Pre-Commit Hooks (Optional)
```bash
# Optional: Husky für lokale Pre-Commit Tests
pnpm add -D husky
npm pkg set scripts.prepare="husky install"
```

## 🎯 Test-Naming Conventions

### Backend (PHPUnit)
```php
class AccountTest extends TestCase
{
    /** @test */
    public function it_creates_account_with_valid_data(): void
    
    /** @test */
    public function it_throws_exception_when_email_is_invalid(): void
    
    /** @test */
    public function it_assigns_default_system_role_to_new_account(): void
}
```

### Frontend (Vitest)
```typescript
describe('LoginForm', () => {
  it('renders email and password fields', () => {})
  
  it('shows validation error for invalid email', () => {})
  
  it('submits form with correct credentials', () => {})
  
  it('displays error message on login failure', () => {})
})
```

## 📝 Test-Datenmanagement

### Backend Factories
- **Model Factories** für Test-Daten
- **Seeder** für komplexe Szenarien
- **States** für verschiedene Daten-Varianten

### Frontend Fixtures
- **Mock Data** für API-Responses
- **Component Props** für verschiedene States
- **Router Mocks** für Navigation-Tests

## ⚡ Performance Testing

### Backend Performance
- **Database Query Optimization**
- **API Response Times**
- **Memory Usage Monitoring**

### Frontend Performance
- **Bundle Size Testing**
- **Component Render Performance**
- **Memory Leak Detection**

## 🐛 Debugging & Monitoring

### Test-Debugging
- **Detailed Error Messages**
- **Stack Traces mit Source-Maps**
- **Interactive Debugging (Vitest UI)**

### Test-Monitoring
- **Test-Execution Times**
- **Flaky Test Detection**
- **Coverage Trends**

---

**Wichtige Hinweise:**
- Tests müssen jederzeit lauffähig sein
- Keine Abhängigkeiten zu externen Services
- Deutsche Fehlermeldungen auch in Tests
- Domain-Isolation auch in Test-Struktur beachten