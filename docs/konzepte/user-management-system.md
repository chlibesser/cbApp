# Benutzerverwaltungssystem V1 - Konzept

## 📋 Analyse der V0-Implementierung

### Account vs. Profile Architektur

**V0 verwendet ein zweistufiges System:**

1. **Account** (Authentifizierung)
   - Globales Login-System
   - Eine Email/Username pro Account
   - Password, 2FA, Login-Attempts
   - Ein Account kann mehrere Profile haben

2. **Profile** (Tenant-spezifische Identität)
   - Jedes Profile gehört zu einem Tenant
   - Ein Account kann Profile in verschiedenen Tenants haben
   - Profile haben tenant-spezifische Rollen und Permissions

### System-Rollen (V0)

```php
enum SystemRole: string {
    case SUPER_ADMIN = 'super_admin';      // Voller Zugriff auf alle Tenants
    case TENANT_ADMIN = 'tenant_admin';    // Verwaltung des eigenen Tenants
    case TENANT_MEMBER = 'tenant_member';  // Normales Mitglied
}
```

### Tenant-Rollen (V0)

- **TenantRole**: Tenant-spezifische Rollen mit Permissions
- **Permissions**: Granulare Berechtigungen pro Tenant-Rolle
- **Profile ↔ TenantRole**: Many-to-Many Beziehung

### Einladungssystem (V0)

#### Workflow:
1. **Tenant-Admin lädt User ein**
   - Erstellt Profile (inactive)
   - Erstellt Invitation mit Token
   - Sendet Email mit Einladungslink

2. **User klickt Einladungslink**
   - Wenn nicht angemeldet → Login/Registrierung erforderlich
   - Wenn angemeldet → Profile wird mit Account verknüpft
   - Profile wird aktiviert

3. **Profile-Account Verknüpfung**
   - Ein Account kann mehrere Profile haben
   - Profile können zu verschiedenen Tenants gehören

## 🎯 V1-Konzept: Verbessertes Benutzerverwaltungssystem

### Core-Prinzipien für V1

1. **Domain-Separation**: Identity, Tenant, Admin getrennt
2. **UUID-basiert**: Alle Entitäten verwenden UUIDs
3. **Event-getrieben**: Events für Einladungen, Profile-Updates
4. **Enum-basiert**: PHP 8.1 Enums statt DB-Enums
5. **Clean Architecture**: Repositories, Services, Policies

### Datenbank-Schema V1

#### accounts (Identity Domain)
```sql
CREATE TABLE accounts (
    id UUID PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP,
    registration_type VARCHAR(50) NOT NULL, -- invitation, direct
    is_active BOOLEAN DEFAULT true,
    last_login_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

#### profiles (Tenant Domain)
```sql
CREATE TABLE profiles (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    account_id UUID REFERENCES accounts(id), -- NULL bis Einladung angenommen
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL, -- Kann von Account-Email abweichen
    phone VARCHAR(50),
    system_role VARCHAR(50) NOT NULL, -- tenant_admin, tenant_member
    is_active BOOLEAN DEFAULT false, -- Erst nach Einladungsannahme
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    
    UNIQUE(tenant_id, email)
);
```

#### invitations (Tenant Domain)
```sql
CREATE TABLE invitations (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    profile_id UUID NOT NULL REFERENCES profiles(id),
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    status VARCHAR(50) NOT NULL, -- pending, accepted, cancelled, expired
    invited_by UUID NOT NULL REFERENCES accounts(id),
    expires_at TIMESTAMP NOT NULL,
    accepted_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### tenant_roles (Tenant Domain)
```sql
CREATE TABLE tenant_roles (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    name VARCHAR(100) NOT NULL,
    description TEXT,
    permissions JSONB, -- Array von Permission-Keys
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE(tenant_id, name)
);
```

#### profile_tenant_roles (Many-to-Many)
```sql
CREATE TABLE profile_tenant_roles (
    profile_id UUID REFERENCES profiles(id),
    tenant_role_id UUID REFERENCES tenant_roles(id),
    created_at TIMESTAMP,
    
    PRIMARY KEY(profile_id, tenant_role_id)
);
```

### Enums für V1

#### SystemRole
```php
enum SystemRole: string {
    case GLOBAL_ADMIN = 'global_admin';
    case TENANT_ADMIN = 'tenant_admin'; 
    case TENANT_MEMBER = 'tenant_member';
    
    public function label(): string {
        return match($this) {
            self::GLOBAL_ADMIN => 'Global Administrator',
            self::TENANT_ADMIN => 'Tenant Administrator',
            self::TENANT_MEMBER => 'Tenant Mitglied',
        };
    }
    
    public function canManageTenantUsers(): bool {
        return $this === self::GLOBAL_ADMIN || $this === self::TENANT_ADMIN;
    }
}
```

#### InvitationStatus
```php
enum InvitationStatus: string {
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    
    public function label(): string {
        return match($this) {
            self::PENDING => 'Ausstehend',
            self::ACCEPTED => 'Angenommen',
            self::CANCELLED => 'Storniert',
            self::EXPIRED => 'Abgelaufen',
        };
    }
}
```

### Services für V1

#### TenantUserService
```php
class TenantUserService {
    public function inviteUser(
        Tenant $tenant, 
        Account $invitedBy,
        InviteUserRequest $request
    ): Invitation;
    
    public function resendInvitation(Profile $profile): Invitation;
    public function cancelInvitation(Invitation $invitation): void;
    public function acceptInvitation(string $token, Account $account): Profile;
    public function updateUserRole(Profile $profile, SystemRole $role): void;
    public function deactivateUser(Profile $profile): void;
    public function removeUser(Profile $profile): void;
}
```

#### ProfileService  
```php
class ProfileService {
    public function createProfile(CreateProfileRequest $request): Profile;
    public function updateProfile(Profile $profile, UpdateProfileRequest $request): Profile;
    public function linkToAccount(Profile $profile, Account $account): Profile;
    public function hasPermission(Profile $profile, string $permission): bool;
}
```

### Events für V1

```php
// Wenn User eingeladen wird
UserInvited::class;

// Wenn Einladung angenommen wird  
InvitationAccepted::class;

// Wenn Profile aktiviert/deaktiviert wird
ProfileStatusChanged::class;

// Wenn Benutzer-Rolle geändert wird
UserRoleChanged::class;
```

## 🔄 Einladungs-Workflow V1

### 1. Einladung erstellen (Tenant-Admin)

```php
POST /api/tenant/users/invite

{
    "first_name": "Max",
    "last_name": "Mustermann", 
    "email": "max@example.com",
    "phone": "+41 79 123 45 67",
    "system_role": "tenant_member",
    "tenant_roles": ["user", "editor"] // Optional
}
```

**Backend-Logik:**
1. Validierung der Eingaben
2. Prüfung: Email bereits in Tenant vorhanden?
3. Erstellung Profile (inactive, ohne account_id)
4. Erstellung Invitation mit Token
5. Event: UserInvited
6. Email-Versand mit Einladungslink

### 2. Einladung anzeigen (Öffentlich zugänglich)

```php
GET /api/invitations/{token}

Response:
{
    "invitation": {
        "email": "max@example.com",
        "tenant_name": "ACME GmbH",
        "profile_name": "Max Mustermann",
        "expires_at": "2025-12-18T12:00:00Z"
    },
    "requires_auth": true
}
```

### 3. Einladung annehmen

**Scenario A: Benutzer hat bereits Account**
```php
POST /api/invitations/{token}/accept
Authorization: Bearer {token}

Response:
{
    "message": "Einladung angenommen",
    "profile": {...},
    "tenant": {...}
}
```

**Scenario B: Neuer Benutzer (Registrierung erforderlich)**
```php
POST /api/invitations/{token}/accept
{
    "username": "maxmuster",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!"
}
```

**Backend-Logik:**
1. Validierung Token & Ablauf
2. Account-Erstellung (falls neu)
3. Profile-Account Verknüpfung
4. Profile Aktivierung
5. Event: InvitationAccepted
6. Automatic Login

## 👥 Benutzeroberfläche V1

### User Management View (für Tenant-Admins)

#### ADT-Konfiguration
```typescript
const userColumns: TableColumn[] = [
    {
        key: 'full_name',
        label: 'Name',
        type: 'text',
        sortable: true,
        searchable: true
    },
    {
        key: 'email', 
        label: 'E-Mail',
        type: 'email',
        sortable: true,
        searchable: true
    },
    {
        key: 'system_role',
        label: 'Rolle',
        type: 'enum',
        sortable: true,
        filterable: true,
        options: SystemRole.options()
    },
    {
        key: 'status',
        label: 'Status', 
        type: 'custom',
        render: 'status-chip'
    },
    {
        key: 'last_login_at',
        label: 'Letzter Login',
        type: 'datetime',
        sortable: true
    }
];
```

#### RSD-Komponenten für User-Management

**UserCreateForm.vue**
- Formular für neue Benutzereinladung
- Validierung in Real-time
- Rollen-Auswahl

**UserEditForm.vue**  
- Bearbeitung bestehender Profile
- Rollen-Verwaltung
- Status-Änderung (aktivieren/deaktivieren)

**UserViewDetails.vue**
- Anzeige aller User-Details
- Login-History
- Einladungs-Status

### Toast-Benachrichtigungen

```typescript
// Erfolg
"Benutzer wurde erfolgreich eingeladen"
"Benutzer-Rolle wurde aktualisiert"  
"Einladung wurde storniert"

// Fehler
"E-Mail-Adresse bereits in diesem Tenant vorhanden"
"Keine Berechtigung für diese Aktion"
"Einladung ist abgelaufen"
```

## 🔐 Berechtigungssystem V1

### Permissions für User-Management

```php
enum TenantPermission: string {
    // User Management
    case INVITE_USERS = 'invite_users';
    case MANAGE_USERS = 'manage_users';
    case VIEW_USERS = 'view_users';
    case CHANGE_USER_ROLES = 'change_user_roles';
    case DEACTIVATE_USERS = 'deactivate_users';
    case REMOVE_USERS = 'remove_users';
    
    // Invitations
    case MANAGE_INVITATIONS = 'manage_invitations';
    case RESEND_INVITATIONS = 'resend_invitations';
    case CANCEL_INVITATIONS = 'cancel_invitations';
}
```

### Standard-Rollen-Konfiguration

**Tenant Admin:**
- Alle User-Management Permissions
- Kann andere Tenant-Admins ernennen
- Kann sich selbst nicht entfernen

**Tenant Member:**
- Nur view_users Permission
- Kann eigenes Profil bearbeiten

## 🚀 Implementation Roadmap

### Phase 1: Core Models & Migrations
1. Account Model erweitern (UUID, bessere Validierung)
2. Profile Model überarbeiten  
3. Invitation Model mit Events
4. Enums implementieren
5. Database Migrations

### Phase 2: Services & Business Logic
1. TenantUserService implementieren
2. ProfileService implementieren  
3. InvitationService implementieren
4. Event Listeners
5. Email Templates

### Phase 3: API Endpoints
1. User Management API (CRUD)
2. Invitation API (invite, accept, cancel, resend)
3. API Tests
4. Validation Rules

### Phase 4: Frontend Implementation  
1. ADT für User-Management
2. RSD-Komponenten (Create, Edit, View)
3. Invitation Flow (öffentliche Seiten)
4. Toast-Integration
5. Error Handling

### Phase 5: Testing & Polish
1. Unit Tests für Services
2. Integration Tests für APIs
3. E2E Tests für Invitation Flow
4. Performance Optimierung
5. Security Review

## 📊 Vergleich V0 → V1

| Aspekt | V0 | V1 |
|--------|----|----|
| **IDs** | Auto-increment | UUIDs |
| **Enums** | DB Enums | PHP 8.1 Enums |
| **Architecture** | Mixed Domains | Clean Domain Separation |
| **Events** | Manual | Event-driven |
| **Validation** | Basic | Comprehensive + German |
| **Frontend** | Basic Tables | ADT + RSD System |
| **Testing** | Limited | Full Test Coverage |
| **Error Handling** | Basic | Toast + Detailed Messages |

## 🎯 Fazit

Das V1 User-Management System baut auf den bewährten Konzepten von V0 auf, verbessert aber:

- **Architektur**: Saubere Domain-Trennung
- **Developer Experience**: Bessere APIs, Events, Services  
- **User Experience**: ADT/RSD UI-System, Toast-Messages
- **Maintainability**: Enums, Tests, Clean Code
- **Security**: Bessere Validierung, Permissions

Das System ist darauf ausgelegt, von Anfang an skalierbar und erweiterbar zu sein.