# View Design Standards

## 🎯 Überblick

Dieses Dokument definiert die einheitlichen Design-Standards für alle Views in cbApp V1. Ziel ist eine konsistente, professionelle und moderne Benutzeroberfläche über alle Module hinweg.

## 📋 Grundprinzipien

1. **Minimalistisch**: Fokus auf Inhalt, keine überladenen UI-Elemente
2. **Konsistent**: Alle Views folgen dem gleichen Muster
3. **Responsiv**: Mobile-first Ansatz
4. **Professionell**: Enterprise-taugliches Design
5. **Datenorientiert**: ADT (Advanced Data Table) als zentrales Element

## 🏗️ Standard View-Struktur

### 1. Basis-Template

```vue
<template>
  <div class="[entity]-view">
    <!-- Standard Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h1 class="text-h4 font-weight-bold">[Titel]</h1>
        <p class="text-subtitle-1 text-medium-emphasis">
          [Beschreibung]
        </p>
      </div>
      
      <!-- Optional: Zähler-Chip -->
      <v-chip 
        v-if="totalCount !== undefined" 
        color="primary" 
        variant="tonal"
        size="large"
        class="px-4"
      >
        {{ totalCount }} [Entities]
      </v-chip>
    </div>

    <!-- Hauptinhalt: ADT in Card -->
    <v-card>
      <AdvancedDataTable
        :entity-config="entityConfig"
        @item-selected="handleItemSelected"
        @item-double-click="handleItemDoubleClick"
        @create="handleCreate"
        @update:count="handleCountUpdate"
      >
        <!-- Custom Slots hier -->
      </AdvancedDataTable>
    </v-card>
  </div>
</template>
```

### 2. View mit Tabs (für komplexere Ansichten)

```vue
<template>
  <div class="[entity]-view">
    <!-- Standard Header (wie oben) -->
    <div class="d-flex align-center justify-space-between mb-4">
      <!-- ... -->
    </div>

    <!-- Tab Navigation -->
    <v-tabs 
      v-model="activeTab" 
      class="mb-4"
      color="primary"
    >
      <v-tab value="overview">Übersicht</v-tab>
      <v-tab value="details">Details</v-tab>
      <v-tab value="statistics">Statistiken</v-tab>
      <v-tab value="settings">Einstellungen</v-tab>
    </v-tabs>

    <!-- Tab Inhalt -->
    <v-window v-model="activeTab">
      <v-window-item value="overview">
        <v-card>
          <AdvancedDataTable ... />
        </v-card>
      </v-window-item>
      
      <v-window-item value="details">
        <v-card>
          <!-- Details-Inhalt -->
        </v-card>
      </v-window-item>
      
      <!-- Weitere Tabs... -->
    </v-window>
  </div>
</template>
```

## 🎨 Design-Elemente

### Header
- **Titel**: `text-h4 font-weight-bold`
- **Untertitel**: `text-subtitle-1 text-medium-emphasis`
- **Layout**: Flexbox mit `justify-space-between`
- **Abstand**: `mb-4` zum nächsten Element

### Zähler-Chip
- **Farbe**: `color="primary"`
- **Variante**: `variant="tonal"`
- **Größe**: `size="large"`
- **Padding**: `class="px-4"`
- **Position**: Rechts im Header

### Cards
- **Wrapper**: Alle Hauptinhalte in `<v-card>`
- **Keine** zusätzlichen Elevations oder Shadows
- **Standard** Border-Radius verwenden

### Abstände
- **Zwischen Hauptelementen**: `mb-4`
- **Card-Padding**: Vuetify Standards verwenden
- **Konsistent** über alle Views

## 📏 Layout-Regeln

### Mobile Responsiveness
```vue
<!-- Responsive Grid für komplexere Layouts -->
<v-row>
  <v-col cols="12" md="8">
    <!-- Hauptinhalt -->
  </v-col>
  <v-col cols="12" md="4">
    <!-- Seiteninhalt -->
  </v-col>
</v-row>
```

### Container
- Views werden bereits in einem Container gerendert
- **Kein** zusätzlicher `v-container` in der View nötig
- Direkt mit dem Content beginnen

## ✅ Dos

1. **DO** verwende die Standard-Header-Struktur
2. **DO** nutze ADT für Tabellen-Darstellungen
3. **DO** halte das Design minimalistisch
4. **DO** verwende konsistente Abstände (mb-4)
5. **DO** nutze Vuetify's Farb-System
6. **DO** implementiere Responsive Design

## ❌ Don'ts

1. **DON'T** erstelle bunte Toolbars
2. **DON'T** verwende zu viele Farben
3. **DON'T** füge unnötige Decorations hinzu
4. **DON'T** weiche vom Standard-Layout ab
5. **DON'T** verwende inline-styles
6. **DON'T** erstelle View-spezifische Design-Patterns

## 📊 Beispiele

### Einfache Listen-View (z.B. Accounts)
```vue
<template>
  <div class="accounts-view">
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h1 class="text-h4 font-weight-bold">Account-Verwaltung</h1>
        <p class="text-subtitle-1 text-medium-emphasis">
          Verwalten Sie alle System-Accounts
        </p>
      </div>
      <v-chip v-if="totalCount" color="primary" variant="tonal" size="large" class="px-4">
        {{ totalCount }} Accounts
      </v-chip>
    </div>

    <v-card>
      <AdvancedDataTable
        :entity-config="accountEntityConfig"
        @create="createAccount"
        @update:count="count => totalCount = count"
      />
    </v-card>
  </div>
</template>
```

### Erweiterte View mit Tabs (z.B. Tenant-Details)
```vue
<template>
  <div class="tenant-details-view">
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h1 class="text-h4 font-weight-bold">{{ tenant.name }}</h1>
        <p class="text-subtitle-1 text-medium-emphasis">
          Tenant-Details und Verwaltung
        </p>
      </div>
      <v-chip color="success" variant="tonal" size="large" class="px-4">
        Aktiv
      </v-chip>
    </div>

    <v-tabs v-model="activeTab" class="mb-4" color="primary">
      <v-tab value="overview">Übersicht</v-tab>
      <v-tab value="users">Benutzer</v-tab>
      <v-tab value="permissions">Berechtigungen</v-tab>
      <v-tab value="settings">Einstellungen</v-tab>
    </v-tabs>

    <v-window v-model="activeTab">
      <v-window-item value="overview">
        <TenantOverview :tenant="tenant" />
      </v-window-item>
      <!-- Weitere Tab-Inhalte -->
    </v-window>
  </div>
</template>
```

## 🚀 Best Practices

1. **Performance**: Lazy-Loading für Tab-Inhalte verwenden
2. **Accessibility**: Proper ARIA Labels und Keyboard Navigation
3. **Loading States**: Konsistente Loading-Indicators
4. **Error Handling**: Einheitliche Error-Darstellung
5. **Empty States**: Aussagekräftige leere Zustände

## 📝 Notizen

- Diese Standards gelten für alle Admin- und Management-Views
- Dashboard-Views können abweichende Designs haben
- Bei Fragen oder Erweiterungen: Team-Diskussion erforderlich
- Dokumentation wird kontinuierlich erweitert