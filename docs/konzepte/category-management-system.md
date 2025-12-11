# 🗂️ Category Management System - cbApp V1

## 📋 Überblick

Flexibles Kategorie-Management-System für Tenant-Admins zur Definition von Kategorie-Gruppen mit detaillierten Beschreibungen für präzise AI-basierte Kategorisierung in cbApp V1.

## 🎯 Kernkonzept

### **Kategorie-Gruppen-Architektur**
```
Tenant → Kategorie-Gruppen → Kategorien → Beschreibungen
```

**Beispiel-Struktur:**
- **Dokumenttyp** (Single-Select)
  - Rechnung: "Alle Rechnungen von Lieferanten..."
  - Vertrag: "Rechtlich bindende Vereinbarungen..."
  - Angebot: "Preisvorschläge ohne Bindung..."

- **Priorität** (Single-Select)
  - Hoch: "Sofortige Bearbeitung erforderlich..."
  - Normal: "Standard-Bearbeitungszeit..."
  - Niedrig: "Kann später bearbeitet werden..."

- **Abteilung** (Multi-Select)
  - HR: "Personal-relevante Dokumente..."
  - Finance: "Finanz- und Buchhaltungsdokumente..."
  - Legal: "Rechtliche Angelegenheiten..."

## 🏗️ Datenbank-Architektur

### **Category Groups (Kategorie-Gruppen)**
```sql
CREATE TABLE category_groups (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID REFERENCES tenants(id),
    
    -- Gruppe-Metadaten
    name VARCHAR(100) NOT NULL,                    -- 'Dokumenttyp'
    slug VARCHAR(100) NOT NULL,                    -- 'document_type'
    description TEXT,                              -- Gruppe-Beschreibung
    
    -- Selection-Verhalten
    selection_type VARCHAR(20) DEFAULT 'single',       -- 'single' oder 'multi' - siehe SelectionType enum
    is_required BOOLEAN DEFAULT false,             -- Pflichtfeld
    
    -- AI-Integration
    ai_enabled BOOLEAN DEFAULT true,               -- AI-Kategorisierung aktiviert
    ai_prompt_context TEXT,                        -- Zusätzlicher AI-Kontext
    ai_confidence_threshold FLOAT DEFAULT 0.7,     -- Mindest-Vertrauen für Auto-Assignment
    
    -- Display-Settings
    display_order INTEGER DEFAULT 0,               -- Reihenfolge in UI
    icon VARCHAR(50),                              -- Icon für UI
    color VARCHAR(20),                             -- Farbe für UI
    
    -- Status
    is_active BOOLEAN DEFAULT true,
    is_system BOOLEAN DEFAULT false,               -- System-Gruppen nicht löschbar
    
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    UNIQUE(tenant_id, slug)
);
```

### **Categories (Kategorien)**
```sql
CREATE TABLE categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    category_group_id UUID REFERENCES category_groups(id) ON DELETE CASCADE,
    tenant_id UUID REFERENCES tenants(id),        -- Denormalisiert für Performance
    
    -- Kategorie-Metadaten
    name VARCHAR(100) NOT NULL,                    -- 'Rechnung'
    slug VARCHAR(100) NOT NULL,                    -- 'invoice'
    description TEXT,                              -- Kurze Beschreibung
    
    -- Detaillierte AI-Beschreibungen
    ai_positive_description TEXT NOT NULL,         -- Was GEHÖRT zu dieser Kategorie
    ai_negative_description TEXT,                  -- Was GEHÖRT NICHT zu dieser Kategorie
    ai_keywords TEXT[],                            -- Schlüsselwörter für AI
    ai_examples TEXT[],                            -- Beispiele für AI-Training
    
    -- Kategorie-Eigenschaften
    is_default BOOLEAN DEFAULT false,              -- Default-Auswahl
    requires_approval BOOLEAN DEFAULT false,       -- Manuelle Bestätigung erforderlich
    
    -- Display-Settings
    display_order INTEGER DEFAULT 0,
    icon VARCHAR(50),
    color VARCHAR(20),
    
    -- Status
    is_active BOOLEAN DEFAULT true,
    
    -- Statistiken
    usage_count INTEGER DEFAULT 0,                 -- Wie oft verwendet
    last_used_at TIMESTAMPTZ,
    
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    UNIQUE(category_group_id, slug)
);
```

### **Category Rules (Kategorisierungs-Regeln)**
```sql
CREATE TABLE category_rules (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
    tenant_id UUID REFERENCES tenants(id),
    
    -- Regel-Definition
    rule_type VARCHAR(20) NOT NULL,               -- Siehe CategoryRuleType enum
    rule_value TEXT NOT NULL,                     -- Regel-Spezifikation
    rule_operator VARCHAR(20) DEFAULT 'contains', -- Siehe CategoryRuleOperator enum
    
    -- Regel-Eigenschaften
    weight FLOAT DEFAULT 1.0,                      -- Gewichtung für AI-Entscheidung
    is_mandatory BOOLEAN DEFAULT false,            -- Muss-Kriterium
    is_exclusion BOOLEAN DEFAULT false,            -- Ausschluss-Kriterium
    
    -- Status
    is_active BOOLEAN DEFAULT true,
    
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);
```

### **Document Categories (Zuordnungen)**
```sql
CREATE TABLE document_categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    document_id UUID REFERENCES documents(id) ON DELETE CASCADE,
    category_id UUID REFERENCES categories(id),
    category_group_id UUID REFERENCES category_groups(id),
    tenant_id UUID REFERENCES tenants(id),
    
    -- Zuordnungs-Metadaten
    assignment_type VARCHAR(20) NOT NULL,         -- Siehe CategoryAssignmentType enum
    confidence_score FLOAT,                       -- AI-Vertrauen (0.0-1.0)
    assigned_by UUID REFERENCES users(id),        -- Bei manueller Zuordnung
    
    -- Approval-Workflow
    status VARCHAR(20) DEFAULT 'auto_approved',   -- Siehe CategoryAssignmentStatus enum
    approved_by UUID REFERENCES users(id),
    approved_at TIMESTAMPTZ,
    
    -- Audit-Trail
    assigned_at TIMESTAMPTZ DEFAULT NOW(),
    
    UNIQUE(document_id, category_group_id)         -- Ein Dokument pro Gruppe nur eine Kategorie (bei single-select)
);
```

## 🎯 Kategorie-Gruppen-Typen

### **1. Document Classification Groups**

#### **Document Type (Single-Select)**
```
Beschreibung: "Art des Dokuments basierend auf Inhalt und Zweck"

Kategorien:
├── Rechnung
│   ├── Positiv: "Zahlungsaufforderungen von Lieferanten, enthält Rechnungsnummer, Betrag, Zahlungsziel"
│   ├── Negativ: "Keine Angebote, Mahnungen oder internen Kostennotizen"
│   └── Keywords: ["Rechnung", "Invoice", "Betrag", "Zahlungsziel", "MwSt"]
├── Vertrag
│   ├── Positiv: "Rechtlich bindende Vereinbarungen zwischen Parteien mit Unterschriften"
│   ├── Negativ: "Keine Angebote, Protokolle oder einfache Korrespondenz"
│   └── Keywords: ["Vertrag", "Agreement", "Unterschrift", "Laufzeit", "Kündigung"]
└── Angebot
    ├── Positiv: "Preisvorschläge ohne Zahlungsverpflichtung, oft mit Gültigkeitsdauer"
    ├── Negativ: "Keine bereits akzeptierten Verträge oder Rechnungen"
    └── Keywords: ["Angebot", "Quote", "Gültigkeit", "unverbindlich", "Preisvorschlag"]
```

#### **Priority Level (Single-Select)**
```
Beschreibung: "Bearbeitungspriorität basierend auf Dringlichkeit und Geschäftsauswirkung"

Kategorien:
├── Kritisch
│   ├── Positiv: "Rechtliche Fristen, Zahlungsaufforderungen, Notfälle, Compliance-relevante Dokumente"
│   └── Keywords: ["Frist", "sofort", "dringend", "Mahnung", "Deadline"]
├── Hoch
│   ├── Positiv: "Wichtige Geschäftsdokumente mit zeitnaher Bearbeitung"
│   └── Keywords: ["wichtig", "zeitnah", "Projekt", "Kunde"]
└── Normal
    ├── Positiv: "Standard-Geschäftsdokumente ohne besonderen Zeitdruck"
    └── Keywords: ["Information", "Routine", "Standard"]
```

### **2. Organizational Groups**

#### **Department (Multi-Select)**
```
Beschreibung: "Zuständige Abteilung(en) für Dokumentenbearbeitung"

Kategorien:
├── Finance
│   ├── Positiv: "Rechnungen, Verträge mit Finanzauswirkung, Budgetdokumente, Steuerunterlagen"
│   └── Keywords: ["€", "$", "Betrag", "Steuer", "Budget", "Kosten"]
├── HR
│   ├── Positiv: "Personalunterlagen, Arbeitsverträge, Gehaltsabrechnungen, Bewerbungen"
│   └── Keywords: ["Personal", "Mitarbeiter", "Gehalt", "Arbeitsvertrag", "Bewerbung"]
├── Legal
│   ├── Positiv: "Verträge, rechtliche Dokumente, Compliance-Unterlagen, Versicherungen"
│   └── Keywords: ["Recht", "Vertrag", "Versicherung", "Compliance", "AGB"]
└── IT
    ├── Positiv: "Software-Lizenzen, technische Dokumentation, IT-Verträge"
    └── Keywords: ["Software", "Lizenz", "technisch", "Server", "IT"]
```

#### **Project Assignment (Multi-Select)**
```
Beschreibung: "Zuordnung zu spezifischen Projekten oder Kunden"

Kategorien:
├── Projekt Alpha
│   ├── Positiv: "Alle Dokumente die explizit Projekt Alpha betreffen oder erwähnen"
│   └── Keywords: ["Alpha", "PA-2024", "Kunde XYZ"]
├── Projekt Beta
│   ├── Positiv: "Dokumente mit Bezug zu Projekt Beta und zugehörigen Aktivitäten"
│   └── Keywords: ["Beta", "PB-2024", "Innovation"]
└── Allgemein
    ├── Positiv: "Dokumente ohne spezifische Projektzuordnung"
    └── Keywords: ["allgemein", "standard", "overhead"]
```

### **3. Compliance & Regulatory Groups**

#### **Data Sensitivity (Single-Select)**
```
Beschreibung: "Datenschutz-Klassifizierung nach GDPR und internen Richtlinien"

Kategorien:
├── Vertraulich
│   ├── Positiv: "Personenbezogene Daten, Geschäftsgeheimnisse, interne Strategien"
│   ├── Negativ: "Öffentlich verfügbare Informationen, allgemeine Geschäftskorrespondenz"
│   └── Keywords: ["vertraulich", "geheim", "personenbezogen", "GDPR"]
├── Intern
│   ├── Positiv: "Interne Dokumente ohne externe Weitergabe-Berechtigung"
│   └── Keywords: ["intern", "nicht öffentlich", "Mitarbeiter"]
└── Öffentlich
    ├── Positiv: "Dokumente die extern geteilt werden können"
    └── Keywords: ["öffentlich", "Marketing", "Website", "Presse"]
```

## 🤖 AI-Prompt-Integration

### **Prompt-Template für Kategorisierung**
```
SYSTEM KONTEXT:
Du hilfst bei der Kategorisierung von Dokumenten für {tenant_name}.

KATEGORIE-GRUPPEN:
{category_groups_context}

KATEGORISIERUNGS-AUFGABE:
Analysiere das folgende Dokument und ordne es den passenden Kategorien zu.

DOKUMENT-CONTENT:
{document_content}

DOKUMENT-METADATA:
- Dateiname: {filename}
- Dateityp: {file_type}
- Größe: {file_size}
- Upload-Datum: {upload_date}

ANWEISUNGEN:
1. Analysiere den Inhalt sorgfältig
2. Berücksichtige die positiven und negativen Beschreibungen jeder Kategorie
3. Nutze die Keywords als Hinweise
4. Gib für jede Kategorie-Gruppe eine Empfehlung ab
5. Begründe deine Entscheidung kurz

ANTWORT-FORMAT:
```json
{
  "kategorisierungen": [
    {
      "gruppe": "document_type",
      "kategorie": "invoice",
      "konfidenz": 0.95,
      "begründung": "Enthält Rechnungsnummer, Betrag und Zahlungsziel"
    },
    {
      "gruppe": "priority",
      "kategorie": "high", 
      "konfidenz": 0.8,
      "begründung": "Zahlungsfrist in 7 Tagen"
    }
  ]
}
```
```

### **Dynamic Prompt Context Builder**
```php
class CategoryPromptBuilder 
{
    public function buildCategoryContext(string $tenantId): string
    {
        $groups = CategoryGroup::where('tenant_id', $tenantId)
            ->where('ai_enabled', true)
            ->with('categories')
            ->orderBy('display_order')
            ->get();
            
        $context = [];
        
        foreach ($groups as $group) {
            $groupContext = [
                "GRUPPE: {$group->name} ({$group->selection_type})",
                "Beschreibung: {$group->description}",
                ""
            ];
            
            foreach ($group->categories as $category) {
                $groupContext[] = "KATEGORIE: {$category->name}";
                $groupContext[] = "✅ GEHÖRT DAZU: {$category->ai_positive_description}";
                
                if ($category->ai_negative_description) {
                    $groupContext[] = "❌ GEHÖRT NICHT DAZU: {$category->ai_negative_description}";
                }
                
                if ($category->ai_keywords) {
                    $groupContext[] = "🔑 KEYWORDS: " . implode(', ', $category->ai_keywords);
                }
                
                $groupContext[] = "";
            }
            
            $context[] = implode("\n", $groupContext);
        }
        
        return implode("\n---\n", $context);
    }
}
```

## 🎨 Frontend-Management

### **Category Group Management (Tenant-Admin)**
```vue
<!-- CategoryGroupManagement.vue -->
<template>
  <v-container>
    <h2>Kategorie-Gruppen verwalten</h2>
    
    <!-- Gruppe erstellen -->
    <v-card class="mb-6">
      <v-card-title>Neue Kategorie-Gruppe</v-card-title>
      <v-card-text>
        <CategoryGroupForm @save="createGroup" />
      </v-card-text>
    </v-card>
    
    <!-- Bestehende Gruppen -->
    <div v-for="group in categoryGroups" :key="group.id">
      <v-expansion-panels class="mb-4">
        <v-expansion-panel>
          <v-expansion-panel-title>
            <div class="d-flex align-center">
              <v-icon :icon="group.icon" class="mr-3" />
              <div>
                <div class="font-weight-medium">{{ group.name }}</div>
                <div class="text-caption">
                  {{ group.categories.length }} Kategorien | 
                  {{ group.selection_type }} | 
                  {{ group.ai_enabled ? 'AI aktiviert' : 'Manuell' }}
                </div>
              </div>
              <v-spacer />
              <v-chip :color="group.is_active ? 'success' : 'error'" size="small">
                {{ group.is_active ? 'Aktiv' : 'Inaktiv' }}
              </v-chip>
            </div>
          </v-expansion-panel-title>
          
          <v-expansion-panel-text>
            <!-- Kategorien-Liste -->
            <CategoryList 
              :group="group" 
              @add-category="addCategory"
              @edit-category="editCategory"
              @delete-category="deleteCategory"
            />
            
            <!-- AI-Settings -->
            <AISettingsPanel 
              v-if="group.ai_enabled"
              :group="group"
              @update-ai-settings="updateAISettings"
            />
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </div>
  </v-container>
</template>
```

### **Category Editor with AI-Descriptions**
```vue
<!-- CategoryEditor.vue -->
<template>
  <v-form @submit.prevent="saveCategory">
    <!-- Basis-Informationen -->
    <v-text-field 
      v-model="form.name" 
      label="Kategorie-Name" 
      required 
    />
    
    <v-textarea 
      v-model="form.description" 
      label="Kurze Beschreibung" 
      rows="2"
    />
    
    <!-- AI-Beschreibungen -->
    <v-divider class="my-4" />
    <h4>AI-Kategorisierung</h4>
    
    <v-textarea
      v-model="form.ai_positive_description"
      label="Was GEHÖRT zu dieser Kategorie?"
      hint="Detaillierte Beschreibung für die AI - was soll klassifiziert werden"
      persistent-hint
      rows="3"
      required
    />
    
    <v-textarea
      v-model="form.ai_negative_description"
      label="Was gehört NICHT zu dieser Kategorie?"
      hint="Abgrenzung - was soll ausgeschlossen werden"
      persistent-hint
      rows="2"
    />
    
    <!-- Keywords -->
    <v-combobox
      v-model="form.ai_keywords"
      label="Schlüsselwörter"
      hint="Wichtige Begriffe die auf diese Kategorie hinweisen"
      persistent-hint
      multiple
      chips
      small-chips
    />
    
    <!-- Beispiele -->
    <v-combobox
      v-model="form.ai_examples"
      label="Beispiele"
      hint="Konkrete Beispiele von Dokumenten dieser Kategorie"
      persistent-hint
      multiple
      chips
    />
    
    <!-- Erweiterte Einstellungen -->
    <v-expansion-panels class="mt-4">
      <v-expansion-panel>
        <v-expansion-panel-title>Erweiterte Einstellungen</v-expansion-panel-title>
        <v-expansion-panel-text>
          <v-switch
            v-model="form.requires_approval"
            label="Manuelle Bestätigung erforderlich"
            hint="AI-Zuordnungen müssen manuell bestätigt werden"
            persistent-hint
          />
          
          <v-switch
            v-model="form.is_default"
            label="Standard-Auswahl"
            hint="Diese Kategorie als Standard verwenden"
            persistent-hint
          />
          
          <!-- Display-Settings -->
          <v-row class="mt-4">
            <v-col cols="6">
              <v-select
                v-model="form.icon"
                :items="iconOptions"
                label="Icon"
              />
            </v-col>
            <v-col cols="6">
              <v-select
                v-model="form.color"
                :items="colorOptions"
                label="Farbe"
              />
            </v-col>
          </v-row>
        </v-expansion-panel-text>
      </v-expansion-panel>
    </v-expansion-panels>
    
    <v-btn type="submit" color="primary" class="mt-4">Speichern</v-btn>
    <v-btn @click="testAIClassification" color="info" class="mt-4 ml-2">
      AI-Test
    </v-btn>
  </v-form>
</template>
```

## 🔄 Kategorisierungs-Workflow

### **1. Document Upload Kategorisierung**
```
Upload → Content-Extraktion → AI-Kategorisierung → Confidence-Check → User-Feedback
```

**Workflow-Schritte:**
1. **Dokument-Upload** → Basis-Metadaten verfügbar
2. **Content-Extraktion** → Text aus PDF/Image
3. **AI-Kategorisierung** → Prompt mit Kategorie-Kontext
4. **Confidence-Evaluation** → Über/unter Threshold?
5. **Auto-Assignment** oder **Manual-Review**
6. **User-Notification** bei unsicherer Kategorisierung

### **2. Batch-Kategorisierung**
```
Dokument-Selection → AI-Batch-Processing → Bulk-Assignment → Review-Dashboard
```

**Features:**
- **Bulk-Kategorisierung** für bestehende Dokumente
- **Preview-Modus** vor finaler Zuordnung  
- **Confidence-Sorting** für effiziente Review
- **Pattern-Learning** aus Manual-Corrections

### **3. Rule-based Kategorisierung**
```
Regel-Definition → Pattern-Matching → Auto-Assignment → Exception-Handling
```

**Regel-Typen:**
- **Keyword-Rules** → "Enthält 'Rechnung'" → Document Type: Invoice
- **Filename-Patterns** → "RE_*.pdf" → Document Type: Invoice  
- **Metadata-Rules** → "Von finance@company.com" → Department: Finance
- **Content-Patterns** → Regex für Rechnungsnummern

## 📊 Analytics & Optimization

### **Category Performance Analytics**
- **Classification Accuracy** → AI vs Manual Corrections
- **Confidence Distribution** → Verteilung der AI-Vertrauenswerte
- **Usage Statistics** → Häufigste Kategorien pro Gruppe
- **Time-to-Classify** → Performance der Kategorisierung

### **AI-Model Optimization**
- **Feedback-Learning** → Manual Corrections verbessern AI
- **Category-Confusion-Matrix** → Wo verwechselt AI Kategorien?
- **Keyword-Effectiveness** → Welche Keywords helfen der AI?
- **Description-Quality** → Impact von Beschreibungs-Details

### **User Experience Metrics**
- **Auto-Classification Rate** → Anteil automatischer Zuordnungen
- **Review-Time** → Zeit für manuelle Kategorisierung
- **User-Satisfaction** → Feedback zur Kategorisierungs-Qualität
- **Workflow-Efficiency** → Zeitersparnis durch Automation

## 🎯 Implementation Roadmap

### **Phase 1: Foundation (Woche 1)**
- [ ] Datenbank-Schema für Category Groups/Categories
- [ ] Basic CRUD für Category Management
- [ ] Category Group Types (Single/Multi-Select)
- [ ] Basic Frontend für Category Creation

### **Phase 2: AI Integration (Woche 2)**
- [ ] AI-Prompt-Builder für Categories
- [ ] Document-Kategorisierung mit AI
- [ ] Confidence-Threshold-System
- [ ] Manual-Review-Interface

### **Phase 3: Advanced Features (Woche 3)**
- [ ] Rule-based Kategorisierung
- [ ] Batch-Processing für bestehende Dokumente
- [ ] Category Performance Analytics
- [ ] AI-Model-Improvement durch Feedback

### **Phase 4: Optimization (Woche 4)**
- [ ] Advanced UI für Category Management
- [ ] Kategorie-Import/Export
- [ ] Template-Categories für neue Tenants
- [ ] Comprehensive Documentation

## 🚀 Expected Benefits

### **Für Tenant-Admins**
- **Flexible Kategorisierung** → Anpassung an Geschäftsprozesse
- **AI-Powered Automation** → Reduzierung manueller Arbeit
- **Granular Control** → Präzise Definition von Kategorien
- **Analytics & Insights** → Verständnis der Dokumenten-Patterns

### **Für End-Users**
- **Automatic Organization** → Dokumente organisieren sich selbst
- **Consistent Classification** → Einheitliche Kategorisierung
- **Smart Search** → Finden durch Kategorien
- **Reduced Manual Work** → Weniger manuelle Zuordnung

### **Für das System**
- **Scalable Intelligence** → Lernt aus Tenant-Definitionen
- **Domain Expertise** → Nutzt Geschäftswissen der Tenants
- **Continuous Improvement** → Wird durch Usage besser
- **Integration Ready** → Basis für Workflow-Automation

---

Dieses **Category Management System** ermöglicht es Tenants, ihre **domainspezifische Expertise** in die **AI-Kategorisierung** einzubringen und dabei **maximale Flexibilität** bei **optimaler Automation** zu erreichen.