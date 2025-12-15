# CLAUDE.md - cbApp V1

## 🎯 Dies ist die NEUE App (Fresh Start)

Dies ist das **komplett neue cbApp V1 Projekt** - ein sauberer Neustart ohne Legacy-Code.

## 📍 Wichtige Referenzen

- **Neue App (diese hier)**: `/cbApp V1/` = V1
- **Alte App (Referenz)**: `/01 Code/` = V0

## 🔄 Wenn du von "der alten App" oder "V0" sprichst

Gemeint ist das System in `/01 Code/` mit:
- Dem bestehenden Backend (Laravel)
- Dem bestehenden Frontend (Vue.js)
- Allen bisherigen Workflows, Email-System, etc.

**Referenz-Namen für die alte App:**
- "die alte App"
- "V0"
- "das bisherige System"
- "Legacy-System"

## 📋 Kernprinzipien für cbApp V1

1. **Fresh Start** - Keine Migration von altem Code, nur Konzepte übernehmen
2. **Foundation First** - Zuerst Layout, Auth-System (Account/Profile), Tenant-Verwaltung
3. **Clean Architecture** - Von Anfang an richtig
4. **Frontend/Backend getrennt** - Komplett separate Projekte
5. **pnpm Package Manager** - IMMER pnpm verwenden, nie npm oder yarn

## 🏗️ Geplante Struktur

```
cbApp V1/
├── backend/        # Laravel API (mit Sanctum)
├── frontend/       # Vue.js SPA (mit Vuetify)
└── CLAUDE.md      # Diese Datei
```

## ⚡ Start-Fokus

1. **Layout & Navigation** - Sauberes UI-Framework
2. **Auth-System** - Account (Login) vs Profile (Tenant-Identity)
3. **Admin-Bereich** - Tenant & User-Verwaltung
4. **Permissions** - Von Anfang an richtig

## ✅ Bereits implementierte Features

### Core-Features:
- **🔐 Auth-System**: Token-basierte Authentifizierung mit Laravel Sanctum
- **👥 Tenant-System**: Multi-Mandantenfähigkeit mit User-Management
- **📄 Document Management**: Upload, AI-Analyse, Kategorisierung
- **🏷️ Kategorie-System**: Hierarchische Kategorien mit Regeln
- **🔄 Workflow-System**: Visual Workflow Builder mit Vue Flow

### UI-Komponenten:
- **📊 ADT**: Advanced Data Table für alle Listen
- **📝 RSD**: Right Side Drawer für CRUD-Operationen
- **🔔 Toast-System**: Feedback für alle Aktionen
- **🎨 Design Standards**: Einheitliches Layout-Pattern

### Infrastruktur:
- **🏗️ Domain-Driven Design**: Saubere Trennung der Domains
- **📘 TypeScript**: Vollständig typisiertes Frontend
- **📦 pnpm**: Als Package Manager
- **🐘 PostgreSQL**: Mit UUID Primary Keys

## 🛠️ Package Manager

**WICHTIG**: In diesem Projekt wird AUSSCHLIESSLICH **pnpm** als Package Manager verwendet:
- `pnpm install` statt `npm install`
- `pnpm add <package>` statt `npm install <package>`
- `pnpm run <script>` statt `npm run <script>`

Claude soll IMMER pnpm verwenden, nie npm oder yarn.

## 📊 UI-Komponenten-System

### **ADT (Advanced Data Table)**
**Definition**: Enterprise-grade Tabellen-Komponente für Datenmanagement

**Funktionen:**
- Server-Side Processing (Pagination, Sortierung, Filterung)
- Entity-basierte Konfiguration über EntityConfig
- Multi-Type-Spalten (Text, Email, Boolean, Date, Number, etc.)
- Resizable Columns mit Min-/Max-Width
- Custom Slots für erweiterte Zelleninhalte
- Automatische Filter-UI generiert aus Spalten-Definitionen
- Event-System für CRUD-Operationen
- TypeScript-typisiert für vollständige IDE-Unterstützung

**Verwendung:**
```vue
<AdvancedDataTable
  :columns="tableColumns"
  :api-endpoint="/api/admin/tenants"
  @create="handleCreate"
  @item-selected="handleItemSelected"
>
  <template #item.status="{ item, value }">
    <v-chip :color="value ? 'success' : 'error'">
      {{ value ? 'Aktiv' : 'Inaktiv' }}
    </v-chip>
  </template>
</AdvancedDataTable>
```

### **RSD (Right Side Drawer)**
**Definition**: Rechte Seitenleiste für Detail-/Bearbeitungsansichten

**Funktionen:**
- Modal-artige Seitenleiste für CRUD-Operationen
- Drei Modi: View, Edit, Create
- Entity-spezifische Komponenten-Loading
- Zentraler Store für State-Management
- Event-System für Success/Error-Handling
- Auto-Refresh der Tabellen nach Änderungen
- Mobile-responsive Design

**Verwendung über Store:**
```typescript
import { useRSDStore } from '@/infrastructure/stores/rsdStore'

const rsdStore = useRSDStore()

// Öffnen für verschiedene Modi
rsdStore.openView('tenant', tenantData)
rsdStore.openEdit('tenant', tenantData) 
rsdStore.openCreate('tenant')
```

## 🚧 Entwicklungsrichtlinien

**WICHTIG**: Solange wir im **Entwicklungsmodus** sind:

### Datenbank-Migrations-Strategie
- **KEINE** `add_*` oder `change_*` Migrationen erstellen
- **IMMER** die **bestehenden Migrationen direkt anpassen**
- Nach Änderungen: `php artisan migrate:fresh --seed`
- Grund: Saubere Migration-History für Production

### Beispiel:
```bash
# RICHTIG ✅
# Bestehende Migration anpassen:
# database/migrations/2025_12_09_202000_create_accounts_table.php

# FALSCH ❌  
# Neue Migration erstellen:
# database/migrations/2025_12_10_123456_add_system_role_to_accounts_table.php
```

### Wann normale Migrations verwenden:
- **Erst ab Production-Release** von V1
- **Niemals** während der Initial-Entwicklungsphase

## 🎯 Enum-Richtlinien

**WICHTIG**: Verwende IMMER PHP 8.1 Backed Enums, NIEMALS Datenbank-Enums!

### Warum PHP Enums?
- ✅ **Typsicherheit**: IDE Support und Autocompletion
- ✅ **Methoden**: `->label()`, `->description()`, `->canDo()`
- ✅ **Wartbarkeit**: Zentrale Definition und Logik
- ✅ **Performance**: Keine DB-Lookups für Labels
- ✅ **Laravel Integration**: Automatische Validation und Casting

### Beispiel:
```php
// RICHTIG ✅
enum SystemRole: string {
    case GLOBAL_ADMIN = 'global_admin';
    
    public function label(): string { 
        return 'Global Administrator'; 
    }
}

// Migration:
$table->string('system_role'); // Als String speichern

// Model:
protected $casts = ['system_role' => SystemRole::class];

// FALSCH ❌
$table->enum('system_role', ['global_admin', 'tenant_admin']);
```

### Wann verwenden:
- Rollen (SystemRole, TenantRole)
- Status (OrderStatus, ProjectStatus)
- Typen (MediaType, NotificationType)
- Alle festen Wertelisten

## 🇩🇪 Sprache

**WICHTIG**: Claude soll IMMER auf Deutsch antworten, es sei denn explizit anders gefordert:
- Alle Antworten auf Deutsch
- Code-Kommentare auf Deutsch (wenn welche nötig sind)
- Commit-Nachrichten auf Deutsch
- Dokumentation auf Deutsch

## 🔧 Arbeitsweise für Code-Änderungen

**WICHTIG**: Bei Code-Änderungen soll Claude IMMER folgendes Verfahren befolgen:

### Was KEINE Freigabe braucht:
- ✅ **Dateien lesen**: IMMER volle Freigabe - Claude kann jederzeit Dateien lesen
- ✅ **Code analysieren**: Suchen, Grep, Glob - alles ohne Rückfrage
- ✅ **Tests ausführen**: Nach Änderungen automatisch testen

### Schrittweise Vorgehensweise bei ÄNDERUNGEN:
1. **Eine Datei pro Änderung** - Immer nur eine einzelne Datei bearbeiten
2. **Änderung ankündigen** - Kurz beschreiben:
   - Welche Datei wird geändert
   - Was ist die konkrete Änderung
   - Warum wird diese Änderung gemacht
3. **Auf Bestätigung warten** - Erst nach "ok" oder expliziter Freigabe fortfahren
4. **Änderung durchführen** - Die angekündigte Änderung umsetzen
5. **Automatisch testen** - Ohne Rückfrage testen (siehe Test-Richtlinien unten)

### Test-Richtlinien:
- **Backend-Endpoints**: MÜSSEN sofort nach Implementierung getestet werden
  - Mit `curl` oder Postman/Thunder Client
  - Erfolgs- und Fehlerszenarien prüfen
  - OHNE Rückfrage beim User
- **Frontend-API-Calls**: MÜSSEN sofort validiert werden
  - Prüfen ob der Endpoint korrekt aufgerufen wird
  - Response-Handling verifizieren
  - OHNE Rückfrage beim User

### Beispiel-Ablauf:
```
Claude: "Ich werde die Datei `backend/app/Http/Controllers/WorkflowController.php` anpassen.

**Änderung**: Füge einen neuen Endpoint `GET /api/workflows/{id}/validate` hinzu.
**Grund**: Dies ermöglicht die Validierung eines Workflows vor der Ausführung.

Soll ich fortfahren?"

User: "ok"

Claude: [führt die Änderung durch]
[testet automatisch den neuen Endpoint mit curl]
[zeigt Testergebnis]
```

### Vorteile dieser Arbeitsweise:
- ✅ **Transparenz**: Jede Änderung ist nachvollziehbar
- ✅ **Kontrolle**: Keine unerwarteten Änderungen
- ✅ **Fokus**: Konzentration auf eine Aufgabe
- ✅ **Qualität**: Sofortige Validierung durch Tests
- ✅ **Review**: Möglichkeit zum Eingreifen vor der Änderung

## ⚠️ Validierungsrichtlinien

**WICHTIG**: Siehe `.claude/validation.md` für detaillierte Validierungsrichtlinien:
- **Nur Backend-Validierung** - keine Frontend-Validierung
- **Deutsche Fehlermeldungen** - alle Validierungsfehler auf Deutsch
- **Vollständige Fehleranzeige** - alle Backend-Fehler müssen im Frontend angezeigt werden
- **Toast-Benachrichtigungen** - bei jeder Daten-Transaktion (Erfolg & Fehler)

## 🚨 Domain-Isolation (KRITISCH)

**⚠️ STRENGE REGEL: Nur Änderungen innerhalb der aktuellen Domain!**

### 🛑 Verbotene Cross-Domain Änderungen
- **Workflow-Domain** ➜ KEINE Änderungen an User/Tenant/Identity
- **User-Domain** ➜ KEINE Änderungen an Workflow/Tenant/Admin  
- **Tenant-Domain** ➜ KEINE Änderungen an Workflow/User/Identity
- **Identity-Domain** ➜ KEINE Änderungen an Workflow/Tenant/User

### ✅ Erlaubte Änderungen
- **NUR** in der Domain, in der aktuell gearbeitet wird
- **NUR** Shared/Core Komponenten wenn explizit erforderlich
- **NUR** Infrastructure Layer für domainspezifische Anpassungen

### 🔍 Warnsignale
```
🚨🚨🚨 ACHTUNG: CROSS-DOMAIN ÄNDERUNG ERKANNT! 🚨🚨🚨
Du arbeitest in [CURRENT_DOMAIN] aber änderst [OTHER_DOMAIN]!
STOPPE SOFORT und frage nach Bestätigung!
```

### 📂 Domain-Struktur
```
backend/app/Domains/
├── Workflow/     # Workflow-spezifische Logik
├── Identity/     # User/Profile Management  
├── Tenant/       # Mandantenverwaltung
└── Admin/        # Systemadministration

frontend/src/domains/
├── workflow/     # Workflow UI & Services
├── identity/     # Login/Profile UI
├── tenant/       # Tenant Management UI  
└── admin/        # Admin Panel UI
```

### 🛡️ Ausnahmen (nur mit expliziter Genehmigung)
- Shared/Core Updates die mehrere Domains betreffen
- Infrastructure-Änderungen für domainübergreifende Features
- Bug-Fixes die mehrere Domains tangieren

## 🗃️ Datenbank-Architektur

**WICHTIG**: Alle IDs in der Datenbank sind **UUIDs**, keine Auto-Increment Integers!

### UUID-Verwendung
- **Accounts**: UUID Primary Keys
- **Alle Relations**: UUID Foreign Keys
- **Morphable Relations**: `uuidMorphs()` statt `morphs()`
- **Personal Access Tokens**: UUID tokenable_id

### Beispiel Migration:
```php
// RICHTIG ✅
$table->uuid('id')->primary();
$table->foreignUuid('account_id')->constrained();
$table->uuidMorphs('tokenable');

// FALSCH ❌
$table->id();
$table->foreignId('account_id')->constrained();
$table->morphs('tokenable');
```

## 🎨 View-Design Standards

**WICHTIG**: Alle Views folgen einem einheitlichen Design-Pattern für Konsistenz und Professionalität.

### Grundprinzipien:
- **Minimalistisch**: Fokus auf Daten, keine überladenen UI-Elemente
- **Einheitlich**: Alle Views verwenden das gleiche Layout-Pattern
- **Modern**: Klares, professionelles Enterprise-Design

### Standard-Struktur:
1. **Header**: Titel + Beschreibung + Zähler-Chip
2. **Optional**: Tab-Navigation für komplexere Views
3. **Hauptinhalt**: ADT in v-card

**Detaillierte Dokumentation**: Siehe `/docs/VIEW_DESIGN_STANDARDS.md`

## 🔐 Token-basierte Authentifizierung

**WICHTIG**: cbApp V1 verwendet ein **reines Token-basiertes Auth-System** ohne Sessions oder CSRF-Tokens!

### Architektur:
- **Laravel Sanctum**: Nur für API Token Management (keine SPA-Features)
- **Stateless API**: Jeder Request wird über Bearer Token authentifiziert
- **Keine Sessions**: `SESSION_DRIVER=array` - keine serverseitigen Sessions
- **Kein CSRF**: Alle State-verändernden Operationen nur über authentifizierte API
- **Token Storage**: Im Frontend localStorage (für Persistenz)

### Auth-Flow:
```typescript
// Login
POST /api/auth/login
Response: { token: "...", user: {...} }

// Alle API-Requests
headers: {
  'Authorization': 'Bearer ' + token,
  'Accept': 'application/json'
}

// Logout
POST /api/auth/logout (invalidiert Token)
```

### Sicherheitsvorteile:
- ✅ **XSS-resistent**: Kein CSRF-Token im DOM/Meta-Tags
- ✅ **Einfache Architektur**: Keine Session-Synchronisation
- ✅ **Skalierbar**: Komplett stateless
- ✅ **Mobile-ready**: Gleiche API für Web/Mobile

### Frontend-Integration:
- **authStore**: Verwaltet Token und User-State
- **apiClient**: Fügt automatisch Bearer Token hinzu
- **Auto-Logout**: Bei 401 Unauthorized
- **Token-Refresh**: Nicht implementiert (by design)

## 🌐 Server-Konfiguration

**Backend (Laravel)**: 
- Port: **8004**
- URL: `http://localhost:8004`
- API Base: `http://localhost:8004/api`
- Start: `./serve.sh` oder `php artisan serve --port=8004`

**Frontend (Vue.js)**: 
- Port: **5173** (Vite Standard)
- Start: `pnpm dev`

**Datenbank (PostgreSQL)**:
- Port: **5432**
- Database: `cbapp_v1`
- Extension: `pgvector` (für zukünftige ML-Features)

---

**Hinweis**: Wenn Claude in diesem Projekt geöffnet wird, weiß er, dass dies die neue App V1 ist und `/01 Code/` die alte App V0 ist.

