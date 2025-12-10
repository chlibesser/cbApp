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

## 🛠️ Package Manager

**WICHTIG**: In diesem Projekt wird AUSSCHLIESSLICH **pnpm** als Package Manager verwendet:
- `pnpm install` statt `npm install`
- `pnpm add <package>` statt `npm install <package>`
- `pnpm run <script>` statt `npm run <script>`

Claude soll IMMER pnpm verwenden, nie npm oder yarn.

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

