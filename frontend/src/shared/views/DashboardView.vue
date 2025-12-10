<template>
  <div class="dashboard">
    <!-- Welcome Section -->
    <v-row class="mb-6">
      <v-col cols="12">
        <v-card class="welcome-card" elevation="1">
          <v-card-text class="pa-6">
            <div class="d-flex align-center mb-4">
              <v-avatar size="64" class="me-4">
                <v-icon size="32">mdi-account-circle</v-icon>
              </v-avatar>
              <div>
                <h2 class="text-h4 font-weight-light mb-1">
                  Willkommen, {{ profile?.display_name || 'Benutzer' }}!
                </h2>
                <p class="text-body-1 text-medium-emphasis mb-0">
                  {{ currentTenant?.name || 'Kein Tenant ausgewählt' }}
                </p>
              </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="d-flex flex-wrap gap-2">
              <v-btn
                variant="outlined"
                color="primary"
                prepend-icon="mdi-plus"
                @click="openRightDrawer"
              >
                Neues Projekt
              </v-btn>
              <v-btn
                variant="outlined"
                prepend-icon="mdi-note-plus"
                @click="showToast"
              >
                Notiz hinzufügen
              </v-btn>
              <v-btn
                variant="outlined"
                prepend-icon="mdi-check-circle-outline"
              >
                Todo erstellen
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Statistics Cards -->
    <v-row class="mb-6">
      <v-col v-for="stat in statistics" :key="stat.title" cols="12" sm="6" md="3">
        <v-card class="stat-card" elevation="1">
          <v-card-text class="pa-4">
            <div class="d-flex align-center">
              <v-icon
                :color="stat.color"
                size="32"
                class="me-3"
              >
                {{ stat.icon }}
              </v-icon>
              <div>
                <div class="text-h6 font-weight-bold">{{ stat.value }}</div>
                <div class="text-caption text-medium-emphasis">{{ stat.title }}</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Recent Activity -->
    <v-row>
      <v-col cols="12" md="8">
        <v-card elevation="1">
          <v-card-title class="d-flex align-center">
            <v-icon class="me-2">mdi-history</v-icon>
            Letzte Aktivitäten
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-0">
            <v-list lines="two">
              <v-list-item
                v-for="(activity, index) in recentActivities"
                :key="index"
                :prepend-icon="activity.icon"
                :title="activity.title"
                :subtitle="activity.subtitle"
              >
                <template #append>
                  <div class="text-caption text-medium-emphasis">
                    {{ activity.time }}
                  </div>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Quick Links -->
      <v-col cols="12" md="4">
        <v-card elevation="1">
          <v-card-title class="d-flex align-center">
            <v-icon class="me-2">mdi-link</v-icon>
            Schnellzugriff
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-0">
            <v-list>
              <v-list-item
                v-for="link in quickLinks"
                :key="link.name"
                :to="link.to"
                :prepend-icon="link.icon"
                :title="link.title"
                :subtitle="link.subtitle"
              />
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Development Info -->
    <v-row v-if="isDevelopment" class="mt-6">
      <v-col cols="12">
        <v-card color="warning" variant="tonal" elevation="1">
          <v-card-text>
            <div class="d-flex align-center">
              <v-icon class="me-3">mdi-test-tube</v-icon>
              <div>
                <div class="font-weight-bold">Entwicklungsmodus</div>
                <div class="text-caption">
                  Environment: {{ environment }} | 
                  Account ID: {{ authStore.account?.id }} | 
                  Tenant ID: {{ currentTenant?.id }}
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useAuthStore } from '../../infrastructure/stores/authStore'
import { useLayoutStore } from '../../infrastructure/stores/layoutStore'

const authStore = useAuthStore()
const layoutStore = useLayoutStore()

const profile = computed(() => authStore.profile)
const currentTenant = computed(() => authStore.currentTenant)
const environment = computed(() => import.meta.env.MODE)
const isDevelopment = computed(() => environment.value === 'development')

// Mock statistics data
const statistics = computed(() => [
  {
    title: 'Projekte',
    value: '12',
    icon: 'mdi-folder-multiple',
    color: 'primary'
  },
  {
    title: 'Todos',
    value: '24',
    icon: 'mdi-check-circle-outline',
    color: 'success'
  },
  {
    title: 'Notizen',
    value: '8',
    icon: 'mdi-note-text',
    color: 'info'
  },
  {
    title: 'Workflows',
    value: '3',
    icon: 'mdi-workflow',
    color: 'warning'
  }
])

// Mock recent activities
const recentActivities = computed(() => [
  {
    icon: 'mdi-file-document-plus',
    title: 'Neue Rechnung erstellt',
    subtitle: 'RG-2024-001 für Demo GmbH',
    time: 'vor 2 Stunden'
  },
  {
    icon: 'mdi-check',
    title: 'Todo abgeschlossen',
    subtitle: 'E-Mail Konfiguration überprüfen',
    time: 'vor 4 Stunden'
  },
  {
    icon: 'mdi-account-plus',
    title: 'Neuer Kontakt hinzugefügt',
    subtitle: 'Max Mustermann',
    time: 'gestern'
  },
  {
    icon: 'mdi-workflow',
    title: 'Workflow ausgeführt',
    subtitle: 'Automatische Rechnungsverarbeitung',
    time: 'vor 2 Tagen'
  }
])

// Mock quick links
const quickLinks = computed(() => [
  {
    name: 'todos',
    title: 'Todos verwalten',
    subtitle: '24 offene Aufgaben',
    icon: 'mdi-check-circle-outline',
    to: '/todos'
  },
  {
    name: 'projects',
    title: 'Projekte',
    subtitle: '12 aktive Projekte',
    icon: 'mdi-folder-multiple',
    to: '/projects'
  },
  {
    name: 'crm',
    title: 'CRM',
    subtitle: 'Kontakte verwalten',
    icon: 'mdi-account-group',
    to: '/crm/contacts'
  },
  {
    name: 'media',
    title: 'Medien',
    subtitle: 'Dateien verwalten',
    icon: 'mdi-image-multiple',
    to: '/media'
  }
])

// Demonstration functions
const openRightDrawer = () => {
  layoutStore.openRightDrawer('ProjectForm', {
    mode: 'create',
    project: null
  }, {
    title: 'Neues Projekt erstellen',
    width: 600
  })
}

const showToast = () => {
  layoutStore.showSuccess('Toast-Benachrichtigung funktioniert!', {
    timeout: 3000
  })
}

onMounted(async () => {
  // Load additional dashboard data here
  console.log('Dashboard mounted')
})
</script>

<style scoped>
.dashboard {
  max-width: 1400px;
  margin: 0 auto;
}

.welcome-card {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.05) 0%, transparent 50%);
  border-left: 4px solid rgb(var(--v-theme-primary));
}

.stat-card {
  transition: transform 0.2s ease-in-out;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.gap-2 {
  gap: 8px;
}

/* Mobile optimizations */
@media (max-width: 600px) {
  .welcome-card .v-card-text {
    padding: 16px !important;
  }
  
  .d-flex.align-center.mb-4 {
    flex-direction: column;
    text-align: center;
  }
  
  .d-flex.align-center.mb-4 .v-avatar {
    margin-bottom: 16px;
    margin-right: 0 !important;
  }
}
</style>