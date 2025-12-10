i# 🤖 AI-System - cbApp V1

## 📋 Überblick

Das AI-System für cbApp V1 basiert auf der bewährten Multi-Provider-Architektur von V0, erweitert um moderne Enterprise-Features und Domain-spezifische Services.

## 🎯 Kernprinzipien

### 1. Multi-Provider-Flexibilität
- **Kosten-Optimierung** durch Provider-Wahl pro Use Case
- **Ausfallsicherheit** durch Provider-Fallbacks
- **Qualitäts-Balance** zwischen lokalen und Cloud-Models

### 2. Template-basierte Prompt-Verwaltung
- **Admin-Level**: Basis-Templates mit Standard-Prompts
- **Tenant-Level**: Anpassbare Prompts und Model-Wahl
- **Schema-Validierung** für strukturierte AI-Outputs

### 3. Domain-driven AI-Services
- **Spezialisierte Services** pro Geschäftsbereich
- **Einheitliche AI-Provider-Abstraction**
- **Robuste Fehlerbehandlung** mit Fallbacks

## 🏗️ DDD-Architektur nach Domains

### Backend Domain-Trennung

#### **Admin Domain** (System-Level AI-Verwaltung)
```
backend/app/Domains/Admin/AI/
├── Models/
│   ├── PromptTemplate.php         # System-weite Basis-Templates
│   ├── TemplateVariable.php       # Variable-Definitionen
│   └── AIProviderConfig.php       # System-Provider-Configs
├── Services/
│   ├── TemplateManagementService.php  # CRUD für Templates
│   ├── SystemAIService.php           # System-AI-Operationen
│   └── ProviderManagementService.php # Provider-Verwaltung
├── Controllers/
│   ├── AITemplateController.php       # Template-CRUD API
│   └── AIProviderController.php       # Provider-Config API
└── Repositories/
    ├── PromptTemplateRepository.php   # Template-Queries
    └── AIProviderRepository.php       # Provider-Queries
```

#### **Tenant Domain** (Tenant-spezifische AI-Konfiguration)
```
backend/app/Domains/Tenant/AI/
├── Models/
│   ├── TenantAITemplate.php       # Tenant-Template-Konfiguration
│   ├── TenantAIProvider.php       # Tenant-Provider-Settings
│   └── AIUsageLog.php             # Tenant-AI-Usage
├── Services/
│   ├── TenantAIService.php        # Tenant-AI-Operationen
│   ├── AIConfigurationService.php # Template-/Provider-Config
│   └── UsageTrackingService.php   # Usage-Monitoring
├── Controllers/
│   ├── TenantAIController.php     # Tenant-AI-Management
│   └── AIAnalyticsController.php  # Usage-Analytics
└── Repositories/
    ├── TenantAIRepository.php     # Tenant-AI-Queries
    └── UsageLogRepository.php     # Usage-Data
```

#### **Shared AI Infrastructure** (Domain-übergreifend)
```
backend/app/Domains/Shared/AI/
├── Providers/
│   ├── AnthropicProvider.php      # Claude Implementation
│   ├── OllamaProvider.php         # LocalAI Implementation
│   ├── OpenAIProvider.php         # OpenAI Implementation
│   └── AIProviderInterface.php    # Unified Interface
├── Services/
│   ├── CoreAIService.php          # Zentrale AI-Engine
│   ├── PromptBuilder.php          # Prompt-Generation
│   ├── RateLimitService.php       # Rate Limiting
│   └── QualityAssuranceService.php # AI-Quality-Checks
├── ValueObjects/
│   ├── AIRequest.php              # AI-Request-DTO
│   ├── AIResponse.php             # AI-Response-DTO
│   └── QualityScore.php           # Quality-Metrics-VO
└── Jobs/
    ├── ProcessAIRequestJob.php    # Async AI-Processing
    └── RetryFailedAIJob.php       # Error Recovery
```

### Frontend Domain-Trennung

#### **Admin Frontend** (System-AI-Verwaltung)
```
frontend/src/domains/admin/ai/
├── views/
│   ├── TemplateManagement.vue     # Basis-Template-Verwaltung
│   ├── ProviderManagement.vue     # System-Provider-Config
│   └── SystemAIAnalytics.vue      # System-weite AI-Metrics
├── components/
│   ├── TemplateEditor.vue         # Template-Editor
│   ├── ProviderConfigForm.vue     # Provider-Konfiguration
│   └── TemplateValidator.vue      # Template-Validation
├── services/
│   └── adminAIApi.js              # Admin-AI-API-Calls
└── stores/
    └── adminAIStore.js            # Admin-AI-State
```

#### **Tenant Frontend** (Tenant-AI-Konfiguration)
```
frontend/src/domains/tenant/ai/
├── views/
│   ├── AITemplateConfig.vue       # Template-Aktivierung/-Anpassung
│   ├── AIProviderSettings.vue     # Provider-Einstellungen
│   └── AIUsageAnalytics.vue       # Usage-Dashboard
├── components/
│   ├── TemplateActivationList.vue # Template-Auswahl
│   ├── CustomPromptEditor.vue     # Custom-Prompt-Editor
│   └── UsageMetrics.vue           # Usage-Anzeige
├── services/
│   └── tenantAIApi.js             # Tenant-AI-API-Calls
└── stores/
    └── tenantAIStore.js           # Tenant-AI-State
```

#### **Shared AI Components** (Domain-übergreifend)
```
frontend/src/shared/ai/
├── components/
│   ├── AIStatusIndicator.vue      # Processing-Status
│   ├── AIResultDisplay.vue        # Result-Anzeige
│   └── ModelSelector.vue          # Model-Auswahl
├── services/
│   ├── coreAIService.js           # Core-AI-Logik
│   └── aiUtils.js                 # AI-Utilities
└── types/
    ├── aiTypes.ts                 # TypeScript-Definitionen
    └── templateTypes.ts           # Template-Types
```

## 🔧 AI-Provider-System

### Unterstützte Provider

#### **1. Anthropic Claude** (Premium)
- **Models**: claude-3-5-sonnet, claude-3-5-haiku, claude-3-opus
- **Use Cases**: Komplexe Analyse, Kreative Aufgaben, Reasoning
- **Kosten**: Hoch, aber beste Qualität
- **Rate Limits**: 4000 RPM (Rate Limit Service)

#### **2. Ollama (LocalAI)** (Standard)
- **Models**: llama3.1, qwen2.5, deepseek-r1, mistral
- **Use Cases**: Standard-Kategorisierung, OCR-Post-Processing
- **Kosten**: Nur Server-Ressourcen
- **Performance**: Lokaler Inference Server

#### **3. OpenAI** (Optional)
- **Models**: gpt-4o, gpt-4o-mini, gpt-3.5-turbo
- **Use Cases**: Backup-Provider, spezielle Features
- **Integration**: API-kompatibel

### Provider-Konfiguration (Tenant-Level)
```
ai_provider_configs:
├── provider_type: "anthropic|ollama|openai"
├── model_name: "claude-3-5-sonnet"
├── api_endpoint: "https://api.anthropic.com"
├── api_key: [verschlüsselt]
├── default_parameters:
│   ├── temperature: 0.1
│   ├── max_tokens: 2000
│   └── timeout: 120
├── rate_limits:
│   ├── requests_per_minute: 100
│   └── tokens_per_day: 1000000
└── fallback_provider: "ollama"
```

## 📝 Template-Management-System

### Template-Hierarchie

#### **Admin-Level (Super-Admin)**
- **Basis-Templates** erstellen und verwalten
- **Standard-Prompts** mit bewährten Patterns
- **Model-Empfehlungen** pro Use Case
- **Performance-Benchmarks** definieren

#### **Tenant-Level (Tenant-Admin)**
- **Templates aktivieren/deaktivieren** aus Admin-Pool
- **Custom Prompts** schreiben oder Standard verwenden
- **Model-Provider wählen** pro Template
- **Parameter fine-tuning** (temperature, tokens)

#### **User-Level (End-User)**
- **Template-Ausführung** mit dynamischen Variablen
- **Ergebnis-Bewertung** für ML-Training
- **Custom Requests** für Ad-hoc-Analyse

### Template-Datenbank-Schema

#### `ai_prompt_templates` (Admin-Basis-Templates)
```sql
CREATE TABLE ai_prompt_templates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    slug VARCHAR(100) UNIQUE NOT NULL,         -- 'document_categorization'
    name VARCHAR(200) NOT NULL,                -- 'Dokument-Kategorisierung'
    description TEXT,
    category VARCHAR(50) NOT NULL,             -- 'document', 'email', 'workflow'
    
    -- Template Content
    system_prompt TEXT NOT NULL,
    user_prompt_template TEXT NOT NULL,        -- Mit {{variables}}
    expected_output_schema JSONB,              -- JSON Schema für Validation
    
    -- Model Configuration
    recommended_provider VARCHAR(50),          -- 'anthropic'
    recommended_model VARCHAR(100),            -- 'claude-3-5-sonnet'
    default_parameters JSONB DEFAULT '{}',     -- temperature, max_tokens, etc.
    
    -- Template Metadata
    use_case_description TEXT,
    performance_notes TEXT,
    example_input JSONB,
    example_output JSONB,
    
    is_system BOOLEAN DEFAULT false,           -- System-Templates nicht löschbar
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);
```

#### `tenant_ai_templates` (Tenant-Konfiguration)
```sql
CREATE TABLE tenant_ai_templates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID REFERENCES tenants(id),
    template_id UUID REFERENCES ai_prompt_templates(id), -- NULL für Custom
    
    -- Customization
    custom_name VARCHAR(200),                  -- Überschreibt Template-Name
    custom_system_prompt TEXT,                 -- NULL = Standard verwenden
    custom_user_prompt TEXT,                   -- NULL = Standard verwenden
    
    -- Provider Override
    provider_type VARCHAR(50),                 -- Überschreibt recommended_provider
    model_name VARCHAR(100),                   -- Überschreibt recommended_model
    parameters JSONB DEFAULT '{}',             -- Custom parameters
    
    -- Status & Usage
    is_active BOOLEAN DEFAULT true,
    is_custom BOOLEAN DEFAULT false,           -- true für tenant-eigene Templates
    usage_count INTEGER DEFAULT 0,
    last_used_at TIMESTAMPTZ,
    
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    UNIQUE(tenant_id, template_id) WHERE template_id IS NOT NULL
);
```

#### `ai_template_variables` (Variable-Definitionen)
```sql
CREATE TABLE ai_template_variables (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    template_id UUID REFERENCES ai_prompt_templates(id),
    variable_name VARCHAR(100) NOT NULL,      -- 'document_content'
    variable_type VARCHAR(50) NOT NULL,       -- 'text', 'json', 'number', 'array'
    description TEXT,
    is_required BOOLEAN DEFAULT true,
    default_value TEXT,
    validation_schema JSONB,                  -- JSON Schema für Validation
    
    created_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(template_id, variable_name)
);
```

## 🎯 Domain-spezifische AI-Services

### 1. Document AI Service

#### Funktionen
- **Auto-Kategorisierung** basierend auf Content-Extraktion
- **Dokument-Zusammenfassung** für schnelle Übersicht
- **Metadata-Extraktion** (Datum, Firma, Betrag)
- **Ähnlichkeits-Suche** zwischen Dokumenten

#### Template-Beispiele
- `document_categorization` - Automatische Kategorie-Zuordnung
- `document_summary` - Inhaltszusammenfassung
- `invoice_extraction` - Rechnungsdaten-Extraktion
- `contract_analysis` - Vertrags-Key-Points

### 2. Email AI Service

#### Funktionen
- **Email-Klassifizierung** (Anfrage, Rechnung, Beschwerde)
- **Sentiment-Analyse** für Kundenbeziehung
- **Action Items Extraktion** aus Email-Threads
- **Auto-Response Suggestions** basierend auf Context

#### Template-Beispiele
- `email_classification` - Email-Typ bestimmen
- `sentiment_analysis` - Stimmung bewerten
- `action_extraction` - ToDos identifizieren
- `response_suggestion` - Antwort-Vorschläge

### 3. Workflow AI Service

#### Funktionen
- **Prozess-Automatisierung** durch AI-Entscheidungen
- **Dynamic Routing** basierend auf Content-Analyse
- **Quality Assurance** für Workflow-Outputs
- **Performance Optimization** Vorschläge

#### Template-Beispiele
- `workflow_decision` - Automatische Routing-Entscheidungen
- `quality_check` - Output-Qualitätsprüfung
- `process_optimization` - Verbesserungsvorschläge

### 4. Business Intelligence Service

#### Funktionen
- **Data Insights** aus unstrukturierten Daten
- **Trend-Analyse** über Dokumente/Emails
- **Risk Assessment** für Geschäftsprozesse
- **Compliance-Checks** automatisiert

#### Template-Beispiele
- `data_insights` - Muster in Geschäftsdaten
- `risk_analysis` - Risiko-Bewertung
- `compliance_check` - Regelkonformität prüfen

## ⚙️ AI-Processing-Pipeline

### Synchroner Flow (Real-time)
1. **Template-Auswahl** durch User/System
2. **Variable-Substitution** in Prompt-Template
3. **Provider-Selection** basierend auf Tenant-Config
4. **Rate-Limit-Check** vor API-Call
5. **AI-Request** mit Timeout-Handling
6. **Response-Validation** gegen Schema
7. **Result-Caching** für Performance

### Asynchroner Flow (Background)
1. **Job-Queue** für zeitaufwendige Analysen
2. **Batch-Processing** für ähnliche Requests
3. **Error-Recovery** mit exponential backoff
4. **Progress-Tracking** für User-Feedback
5. **Result-Notification** bei Completion

### Error-Handling-Strategy
- **Provider-Fallback** bei API-Failures
- **Retry-Logic** mit increasing delays
- **Graceful Degradation** bei AI-Unavailability
- **Human-Fallback** für kritische Prozesse

## 📊 Monitoring & Analytics

### AI-Usage-Tracking
- **Request-Volume** pro Tenant/Template
- **Cost-Tracking** pro Provider/Model
- **Performance-Metrics** (Latenz, Success-Rate)
- **Quality-Scores** durch User-Feedback

### Business-Intelligence
- **AI-ROI-Calculation** durch Zeitersparnis
- **Template-Effectiveness** durch Success-Rates
- **User-Adoption** Patterns
- **Cost-Optimization** Recommendations

## 🔒 Security & Compliance

### Data Privacy
- **Tenant-Isolation** für AI-Requests
- **Data-Anonymization** in Logs
- **GDPR-Compliance** für AI-Processing
- **API-Key-Encryption** für Provider-Credentials

### Rate Limiting & Cost Control
- **Per-Tenant-Limits** für AI-Usage
- **Cost-Budgets** mit Alerts
- **Emergency-Shutoffs** bei Limit-Überschreitung
- **Usage-Analytics** für Cost-Optimization

## 🚀 Implementation-Phasen

### Phase 1: Foundation (Wochen 1-2)
- **Core AI-Provider-Integration** (Anthropic, Ollama)
- **Basic Template-System** mit CRUD
- **Document-Kategorisierung** als Pilot-Feature
- **Admin-Interface** für Template-Management

### Phase 2: Enhancement (Wochen 3-4)
- **Email-AI-Integration** für Workflow-Triggers
- **Advanced Template-Features** (Variablen, Schema)
- **Rate-Limiting & Cost-Control**
- **User-Interface** für Template-Configuration

### Phase 3: Intelligence (Wochen 5-6)
- **Business-Intelligence-Features**
- **Advanced Analytics & Monitoring**
- **Performance-Optimization**
- **Machine-Learning-Integration** für Template-Tuning

## 💡 Best Practices

### Prompt-Engineering
- **Clear Instructions** mit Examples
- **Structured Output** durch JSON-Schema
- **Context-Optimization** für Token-Efficiency
- **Error-Handling** Instructions in Prompts

### Performance-Optimization
- **Model-Selection** basierend auf Use Case Complexity
- **Batch-Processing** für ähnliche Requests
- **Caching-Strategy** für häufige Patterns
- **Asynchronous Processing** für Non-Critical Tasks

### User Experience
- **Progressive Disclosure** für AI-Features
- **Clear Status-Indicators** für Processing
- **Fallback-Options** bei AI-Failures
- **Transparency** über AI-Decision-Making

## 🔌 Domain-spezifische API-Endpunkte

### **Admin Domain APIs** (System-Level)
```
# Template-Management (Admin)
GET    /api/admin/ai/templates              # Alle System-Templates
POST   /api/admin/ai/templates              # Neues System-Template
PUT    /api/admin/ai/templates/{id}         # System-Template bearbeiten
DELETE /api/admin/ai/templates/{id}         # System-Template löschen
POST   /api/admin/ai/templates/{id}/validate # Template-Validierung
GET    /api/admin/ai/templates/{id}/usage   # Template-Usage-Stats

# Provider-Management (Admin)
GET    /api/admin/ai/providers              # System-Provider-Configs
POST   /api/admin/ai/providers              # System-Provider erstellen
PUT    /api/admin/ai/providers/{id}         # System-Provider bearbeiten
DELETE /api/admin/ai/providers/{id}         # System-Provider löschen

# System-Analytics (Admin)
GET    /api/admin/ai/analytics/overview     # System-weite AI-Metrics
GET    /api/admin/ai/analytics/tenants      # Tenant-Usage-Übersicht
GET    /api/admin/ai/analytics/costs        # Kosten-Breakdown
GET    /api/admin/ai/analytics/performance  # System-Performance-Metrics
```

### **Tenant Domain APIs** (Tenant-Level)
```
# Template-Konfiguration (Tenant-Admin)
GET    /api/tenant/ai/templates/available   # Verfügbare System-Templates
GET    /api/tenant/ai/templates/active      # Aktive Tenant-Templates
POST   /api/tenant/ai/templates/activate    # System-Template aktivieren
POST   /api/tenant/ai/templates/custom      # Custom Template erstellen
PUT    /api/tenant/ai/templates/{id}        # Tenant-Template bearbeiten
PUT    /api/tenant/ai/templates/{id}/prompt # Custom Prompt bearbeiten
DELETE /api/tenant/ai/templates/{id}        # Tenant-Template deaktivieren

# Provider-Settings (Tenant-Admin)
GET    /api/tenant/ai/providers             # Tenant-Provider-Configs
POST   /api/tenant/ai/providers             # Tenant-Provider-Config erstellen
PUT    /api/tenant/ai/providers/{id}        # Tenant-Provider bearbeiten
DELETE /api/tenant/ai/providers/{id}        # Tenant-Provider löschen
POST   /api/tenant/ai/providers/{id}/test   # Provider-Verbindung testen

# Usage-Analytics (Tenant-Admin)
GET    /api/tenant/ai/usage/overview        # Tenant-AI-Usage-Übersicht
GET    /api/tenant/ai/usage/templates       # Template-Usage-Details
GET    /api/tenant/ai/usage/costs           # Kosten-Tracking
GET    /api/tenant/ai/usage/limits          # Rate-Limit-Status
```

### **Shared AI APIs** (Cross-Domain)
```
# Core AI Processing (Shared)
POST   /api/ai/process                      # AI-Request ausführen
POST   /api/ai/templates/{slug}/execute     # Template mit Variablen ausführen
GET    /api/ai/status/{requestId}           # Async Request Status
POST   /api/ai/batch                       # Batch-Processing
POST   /api/ai/feedback                    # User-Feedback für AI-Results
```

## 🎨 Frontend-Integration

### AI-Template-Editor (Tenant-Admin)
```vue
<!-- frontend/src/domains/ai/views/TemplateManagement.vue -->
<template>
  <v-container>
    <h2>AI-Templates konfigurieren</h2>
    
    <!-- Verfügbare Admin-Templates -->
    <v-card class="mb-6">
      <v-card-title>Verfügbare Templates</v-card-title>
      <v-data-table 
        :headers="templateHeaders" 
        :items="availableTemplates"
        @click:row="activateTemplate"
      >
        <template #item.category="{ item }">
          <v-chip :color="getCategoryColor(item.category)">
            {{ getCategoryLabel(item.category) }}
          </v-chip>
        </template>
        <template #item.provider="{ item }">
          <v-chip variant="outlined">
            {{ item.recommended_provider }}
          </v-chip>
        </template>
        <template #item.actions="{ item }">
          <v-btn @click="activateTemplate(item)" size="small" color="primary">
            Aktivieren
          </v-btn>
        </template>
      </v-data-table>
    </v-card>
    
    <!-- Aktive Templates -->
    <v-card>
      <v-card-title>Konfigurierte Templates</v-card-title>
      <v-expansion-panels>
        <v-expansion-panel v-for="template in activeTemplates" :key="template.id">
          <v-expansion-panel-title>
            <div class="d-flex align-center">
              <v-icon :icon="getTemplateIcon(template.category)" class="mr-2" />
              {{ template.name }}
              <v-spacer />
              <v-chip size="small" :color="template.is_custom ? 'warning' : 'success'">
                {{ template.is_custom ? 'Custom' : 'Standard' }}
              </v-chip>
            </div>
          </v-expansion-panel-title>
          <v-expansion-panel-text>
            <TemplateConfigForm 
              :template="template" 
              @save="updateTemplate"
              @test="testTemplate"
            />
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </v-card>
  </v-container>
</template>
```

### AI-Provider-Konfiguration
```vue
<!-- frontend/src/domains/ai/components/ProviderConfigForm.vue -->
<template>
  <v-form @submit.prevent="saveProvider">
    <!-- Provider-Typ Auswahl -->
    <v-select
      v-model="form.provider_type"
      :items="providerTypes"
      label="AI-Provider"
      @update:model-value="onProviderChange"
    />
    
    <!-- Anthropic Konfiguration -->
    <div v-if="form.provider_type === 'anthropic'" class="mt-4">
      <v-text-field 
        v-model="form.api_key" 
        label="Anthropic API Key" 
        type="password" 
        required 
      />
      <v-select
        v-model="form.model_name"
        :items="anthropicModels"
        label="Model auswählen"
      />
      <v-text-field
        v-model="form.parameters.temperature"
        label="Temperature (0.0-1.0)"
        type="number"
        step="0.1"
        min="0"
        max="1"
      />
      <v-text-field
        v-model="form.parameters.max_tokens"
        label="Max Tokens"
        type="number"
      />
    </div>
    
    <!-- Ollama Konfiguration -->
    <div v-if="form.provider_type === 'ollama'" class="mt-4">
      <v-text-field
        v-model="form.api_endpoint"
        label="Ollama Server URL"
        placeholder="http://localhost:11434"
      />
      <v-select
        v-model="form.model_name"
        :items="ollamaModels"
        label="Model auswählen"
        @click:prepend="refreshOllamaModels"
      />
    </div>
    
    <!-- Rate Limits -->
    <v-divider class="my-4" />
    <h4>Rate Limits</h4>
    <v-row>
      <v-col cols="6">
        <v-text-field
          v-model="form.rate_limits.requests_per_minute"
          label="Requests/Minute"
          type="number"
        />
      </v-col>
      <v-col cols="6">
        <v-text-field
          v-model="form.rate_limits.tokens_per_day"
          label="Tokens/Tag"
          type="number"
        />
      </v-col>
    </v-row>
    
    <!-- Kosten-Budget -->
    <v-text-field
      v-model="form.monthly_budget_usd"
      label="Monatliches Budget (USD)"
      type="number"
      step="0.01"
      prefix="$"
    />
    
    <v-btn type="submit" color="primary" class="mt-4">Speichern</v-btn>
    <v-btn @click="testConnection" color="info" class="mt-4 ml-2">
      Verbindung testen
    </v-btn>
  </v-form>
</template>
```

### AI-Request-Status-Component
```vue
<!-- frontend/src/domains/ai/components/AIStatusIndicator.vue -->
<template>
  <div class="ai-status-indicator">
    <!-- Processing Status -->
    <v-alert
      v-if="status === 'processing'"
      type="info"
      variant="tonal"
      class="mb-4"
    >
      <v-row align="center">
        <v-col cols="auto">
          <v-progress-circular indeterminate size="20" />
        </v-col>
        <v-col>
          <div>AI analysiert Ihre Anfrage...</div>
          <div class="text-caption">
            Provider: {{ request.provider }} | Model: {{ request.model }}
          </div>
        </v-col>
      </v-row>
      <v-progress-linear 
        v-if="request.progress" 
        :model-value="request.progress" 
        class="mt-2"
      />
    </v-alert>
    
    <!-- Success Result -->
    <v-card v-else-if="status === 'completed'" class="mb-4">
      <v-card-title class="d-flex align-center">
        <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
        AI-Analyse abgeschlossen
      </v-card-title>
      <v-card-text>
        <AIResultDisplay :result="result" :template="template" />
      </v-card-text>
      <v-card-actions>
        <v-btn @click="copyResult" size="small" variant="outlined">
          Ergebnis kopieren
        </v-btn>
        <v-btn @click="retryRequest" size="small" variant="outlined">
          Erneut ausführen
        </v-btn>
        <v-spacer />
        <v-btn @click="provideFeedback" size="small" color="primary">
          Feedback
        </v-btn>
      </v-card-actions>
    </v-card>
    
    <!-- Error State -->
    <v-alert
      v-else-if="status === 'failed'"
      type="error"
      variant="tonal"
      class="mb-4"
    >
      <div class="font-weight-medium">AI-Request fehlgeschlagen</div>
      <div class="text-caption">{{ errorMessage }}</div>
      <v-btn @click="retryRequest" size="small" class="mt-2" variant="outlined">
        Erneut versuchen
      </v-btn>
    </v-alert>
  </div>
</template>
```

## 📈 Analytics & Reporting

### AI-Usage-Dashboard (Tenant-Admin)
```vue
<!-- frontend/src/domains/ai/views/AIAnalytics.vue -->
<template>
  <v-container>
    <h2>AI-Nutzung & Performance</h2>
    
    <!-- Überblick-Karten -->
    <v-row class="mb-6">
      <v-col cols="3">
        <MetricCard
          title="Requests heute"
          :value="metrics.requests_today"
          icon="mdi-robot"
          color="primary"
        />
      </v-col>
      <v-col cols="3">
        <MetricCard
          title="Kosten (MTD)"
          :value="formatCurrency(metrics.costs_mtd)"
          icon="mdi-currency-usd"
          color="warning"
        />
      </v-col>
      <v-col cols="3">
        <MetricCard
          title="Erfolgsrate"
          :value="metrics.success_rate + '%'"
          icon="mdi-check-circle"
          color="success"
        />
      </v-col>
      <v-col cols="3">
        <MetricCard
          title="Ø Antwortzeit"
          :value="metrics.avg_response_time + 's'"
          icon="mdi-timer"
          color="info"
        />
      </v-col>
    </v-row>
    
    <!-- Usage-Trends -->
    <v-card class="mb-6">
      <v-card-title>Usage-Trends (30 Tage)</v-card-title>
      <v-card-text>
        <ApexChart
          type="line"
          :options="chartOptions"
          :series="usageSeries"
          height="300"
        />
      </v-card-text>
    </v-card>
    
    <!-- Template-Performance -->
    <v-card class="mb-6">
      <v-card-title>Template-Performance</v-card-title>
      <v-data-table
        :headers="templatePerformanceHeaders"
        :items="templatePerformance"
        :sort-by="[{ key: 'usage_count', order: 'desc' }]"
      >
        <template #item.success_rate="{ item }">
          <v-progress-linear
            :model-value="item.success_rate"
            :color="item.success_rate > 90 ? 'success' : 'warning'"
            height="8"
          />
          <span class="text-caption">{{ item.success_rate }}%</span>
        </template>
        <template #item.avg_cost="{ item }">
          {{ formatCurrency(item.avg_cost) }}
        </template>
      </v-data-table>
    </v-card>
    
    <!-- Provider-Vergleich -->
    <v-card>
      <v-card-title>Provider-Performance</v-card-title>
      <v-card-text>
        <ProviderComparisonChart :data="providerStats" />
      </v-card-text>
    </v-card>
  </v-container>
</template>
```

## 🔧 Service-Implementierung-Details

### Rate Limiting Service
```php
// backend/app/Domains/AI/Services/RateLimitService.php
class RateLimitService
{
    public function checkRateLimit(string $tenantId, string $templateSlug): bool
    {
        $limits = $this->getTenantLimits($tenantId);
        
        // Requests per minute check
        $currentMinute = now()->format('Y-m-d H:i');
        $requestsThisMinute = Cache::get("ai_requests:{$tenantId}:{$currentMinute}", 0);
        
        if ($requestsThisMinute >= $limits['requests_per_minute']) {
            Log::warning('Rate limit exceeded', [
                'tenant_id' => $tenantId,
                'template' => $templateSlug,
                'requests_this_minute' => $requestsThisMinute
            ]);
            return false;
        }
        
        // Daily token limit check
        $tokensToday = $this->getTokenUsageToday($tenantId);
        if ($tokensToday >= $limits['tokens_per_day']) {
            return false;
        }
        
        // Budget check
        $costToday = $this->getCostToday($tenantId);
        if ($costToday >= $limits['daily_budget_usd']) {
            return false;
        }
        
        return true;
    }
    
    public function recordUsage(string $tenantId, AIRequest $request): void
    {
        $currentMinute = now()->format('Y-m-d H:i');
        
        // Increment request counter
        Cache::increment("ai_requests:{$tenantId}:{$currentMinute}", 1);
        Cache::expire("ai_requests:{$tenantId}:{$currentMinute}", 3600);
        
        // Record token usage
        $this->recordTokenUsage($tenantId, $request->tokens_used);
        
        // Record cost
        $this->recordCost($tenantId, $request->estimated_cost);
        
        // Store detailed metrics
        AIUsageLog::create([
            'tenant_id' => $tenantId,
            'template_slug' => $request->template_slug,
            'provider' => $request->provider,
            'model' => $request->model,
            'tokens_used' => $request->tokens_used,
            'response_time_ms' => $request->response_time_ms,
            'success' => $request->success,
            'cost_usd' => $request->estimated_cost
        ]);
    }
}
```

### Prompt Builder Service
```php
// backend/app/Domains/AI/Services/PromptBuilder.php
class PromptBuilder
{
    public function buildPrompt(
        PromptTemplate $template, 
        array $variables, 
        string $tenantId
    ): array {
        
        // 1. Load tenant customizations
        $tenantTemplate = $this->getTenantTemplate($template->id, $tenantId);
        
        $systemPrompt = $tenantTemplate->custom_system_prompt ?? $template->system_prompt;
        $userPrompt = $tenantTemplate->custom_user_prompt ?? $template->user_prompt_template;
        
        // 2. Validate variables
        $this->validateVariables($template, $variables);
        
        // 3. Process template variables
        $processedSystemPrompt = $this->substituteVariables($systemPrompt, $variables);
        $processedUserPrompt = $this->substituteVariables($userPrompt, $variables);
        
        // 4. Add system context
        $systemContext = $this->buildSystemContext($tenantId);
        $finalSystemPrompt = $systemContext . "\n\n" . $processedSystemPrompt;
        
        return [
            'system' => $finalSystemPrompt,
            'user' => $processedUserPrompt,
            'expected_schema' => $template->expected_output_schema
        ];
    }
    
    private function buildSystemContext(string $tenantId): string
    {
        $tenant = Tenant::find($tenantId);
        $context = [
            "Du arbeitest für: {$tenant->name}",
            "Branche: {$tenant->industry}",
            "Land: {$tenant->country}",
            "Sprache: Deutsch",
            "Datum: " . now()->format('d.m.Y'),
        ];
        
        return implode("\n", $context);
    }
    
    private function substituteVariables(string $template, array $variables): string
    {
        $processed = $template;
        
        foreach ($variables as $key => $value) {
            $placeholder = "{{" . $key . "}}";
            
            // Type-specific processing
            $processedValue = match(gettype($value)) {
                'array' => json_encode($value, JSON_PRETTY_PRINT),
                'object' => json_encode($value, JSON_PRETTY_PRINT),
                'boolean' => $value ? 'true' : 'false',
                default => (string) $value
            };
            
            $processed = str_replace($placeholder, $processedValue, $processed);
        }
        
        // Check for unsubstituted variables
        if (preg_match('/\{\{[^}]+\}\}/', $processed)) {
            throw new IncompleteVariableSubstitutionException(
                "Nicht alle Template-Variablen wurden ersetzt"
            );
        }
        
        return $processed;
    }
}
```

### AI Quality Assurance Service
```php
// backend/app/Domains/AI/Services/QualityAssuranceService.php
class QualityAssuranceService
{
    public function validateAIResponse(
        array $response, 
        PromptTemplate $template,
        array $inputData
    ): QualityScore {
        
        $qualityScore = new QualityScore();
        
        // 1. Schema validation
        $schemaValid = $this->validateResponseSchema($response, $template->expected_output_schema);
        $qualityScore->schema_compliance = $schemaValid ? 100 : 0;
        
        // 2. Content quality checks
        $qualityScore->content_relevance = $this->assessContentRelevance($response, $inputData);
        $qualityScore->completeness = $this->assessCompleteness($response, $template);
        $qualityScore->accuracy = $this->assessAccuracy($response, $inputData);
        
        // 3. Consistency checks
        $qualityScore->consistency = $this->checkConsistency($response);
        
        // 4. Overall score calculation
        $qualityScore->overall_score = (
            $qualityScore->schema_compliance * 0.3 +
            $qualityScore->content_relevance * 0.25 +
            $qualityScore->completeness * 0.25 +
            $qualityScore->accuracy * 0.20
        );
        
        // 5. Quality threshold check
        if ($qualityScore->overall_score < 70) {
            Log::warning('Low AI response quality detected', [
                'template_slug' => $template->slug,
                'quality_score' => $qualityScore->overall_score,
                'issues' => $qualityScore->getIssues()
            ]);
        }
        
        return $qualityScore;
    }
    
    public function assessContentRelevance(array $response, array $inputData): float
    {
        // Use semantic similarity or keyword matching
        $relevanceScore = 0.0;
        
        if (isset($response['content']) && isset($inputData['text'])) {
            // Simple keyword overlap for now
            $responseWords = $this->extractKeywords($response['content']);
            $inputWords = $this->extractKeywords($inputData['text']);
            
            $overlap = array_intersect($responseWords, $inputWords);
            $relevanceScore = (count($overlap) / max(count($inputWords), 1)) * 100;
        }
        
        return min($relevanceScore, 100);
    }
}
```

## 🎛️ Admin-Interface Features

### Template-Performance-Analytics
- **Success-Rate-Tracking** pro Template
- **Response-Time-Monitoring**
- **Cost-per-Request-Analysis**
- **User-Satisfaction-Scores**

### Provider-Cost-Optimization
- **Auto-Model-Selection** basierend auf Complexity
- **Cost-Budget-Alerts** bei Überschreitung
- **Usage-Pattern-Analysis** für Optimierung
- **Provider-Performance-Benchmarking**

### Quality-Monitoring
- **AI-Response-Quality-Scores**
- **Schema-Compliance-Tracking**
- **User-Feedback-Integration**
- **Automated-Quality-Alerts**

Das AI-System bildet die **zentrale Intelligence-Layer** für cbApp V1 und ermöglicht intelligente Automatisierung in allen Geschäftsprozessen.