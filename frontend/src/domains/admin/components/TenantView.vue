<template>
  <div class="tenant-view">
    <v-card>
      <v-card-text>
        <div v-if="loading" class="text-center py-8">
          <v-progress-circular indeterminate />
          <p class="mt-2">Tenant-Details werden geladen...</p>
        </div>

        <div v-else-if="tenant">
          <!-- Tenant Header -->
          <div class="d-flex align-center mb-6">
            <v-avatar size="48" color="primary" class="mr-3">
              <v-icon color="white">mdi-office-building</v-icon>
            </v-avatar>
            <div>
              <h2 class="text-h5 font-weight-bold">{{ tenant.name }}</h2>
              <p class="text-body-2 text-medium-emphasis mb-0">{{ tenant.slug }}</p>
            </div>
          </div>

          <!-- Tenant Details -->
          <v-row>
            <v-col cols="12" md="6">
              <v-card variant="outlined">
                <v-card-title class="text-h6 bg-grey-lighten-5">
                  <v-icon class="mr-2">mdi-information</v-icon>
                  Allgemeine Informationen
                </v-card-title>
                <v-card-text>
                  <v-list lines="two">
                    <v-list-item>
                      <v-list-item-title>Name</v-list-item-title>
                      <v-list-item-subtitle>{{ tenant.name }}</v-list-item-subtitle>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>Slug</v-list-item-title>
                      <v-list-item-subtitle>{{ tenant.slug }}</v-list-item-subtitle>
                    </v-list-item>
                    
                    <v-list-item v-if="tenant.description">
                      <v-list-item-title>Beschreibung</v-list-item-title>
                      <v-list-item-subtitle>{{ tenant.description }}</v-list-item-subtitle>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>Typ</v-list-item-title>
                      <v-list-item-subtitle>
                        <v-chip 
                          :color="tenant.is_personal ? 'info' : 'primary'"
                          size="small"
                          variant="flat"
                        >
                          <v-icon size="small" class="mr-1">
                            {{ tenant.is_personal ? 'mdi-account' : 'mdi-office-building' }}
                          </v-icon>
                          {{ tenant.is_personal ? 'Persönlich' : 'Unternehmen' }}
                        </v-chip>
                      </v-list-item-subtitle>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>Status</v-list-item-title>
                      <v-list-item-subtitle>
                        <v-chip 
                          :color="tenant.is_active ? 'success' : 'error'"
                          size="small"
                          variant="flat"
                        >
                          <v-icon size="small" class="mr-1">
                            {{ tenant.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}
                          </v-icon>
                          {{ tenant.is_active ? 'Aktiv' : 'Inaktiv' }}
                        </v-chip>
                      </v-list-item-subtitle>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" md="6">
              <v-card variant="outlined">
                <v-card-title class="text-h6 bg-grey-lighten-5">
                  <v-icon class="mr-2">mdi-chart-line</v-icon>
                  Statistiken
                </v-card-title>
                <v-card-text>
                  <v-list lines="two">
                    <v-list-item>
                      <v-list-item-title>Benutzer</v-list-item-title>
                      <v-list-item-subtitle>
                        <v-chip color="info" size="small" variant="outlined">
                          {{ tenant.users_count || 0 }} Benutzer
                        </v-chip>
                      </v-list-item-subtitle>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>Erstellt am</v-list-item-title>
                      <v-list-item-subtitle>{{ formatDate(tenant.created_at) }}</v-list-item-subtitle>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>Zuletzt aktualisiert</v-list-item-title>
                      <v-list-item-subtitle>{{ formatDate(tenant.updated_at) }}</v-list-item-subtitle>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Tenant Users Section -->
          <div class="mt-6">
            <div class="d-flex justify-space-between align-center mb-4">
              <h3 class="text-h6">Benutzer in diesem Tenant</h3>
              <v-btn
                color="primary"
                variant="outlined"
                @click="openUserManagement"
              >
                <v-icon class="mr-1">mdi-account-plus</v-icon>
                Benutzer verwalten
              </v-btn>
            </div>

            <v-card variant="outlined">
              <v-card-text>
                <div v-if="loadingUsers" class="text-center py-4">
                  <v-progress-circular indeterminate size="32" />
                  <p class="mt-2">Benutzer werden geladen...</p>
                </div>

                <div v-else-if="users.length === 0" class="text-center py-8">
                  <v-icon size="64" color="grey-lighten-1">mdi-account-off</v-icon>
                  <p class="text-h6 mt-2">Keine Benutzer zugewiesen</p>
                  <p class="text-body-2 text-medium-emphasis">
                    Diesem Tenant sind noch keine Benutzer zugewiesen.
                  </p>
                  <v-btn color="primary" @click="openUserManagement">
                    Ersten Benutzer hinzufügen
                  </v-btn>
                </div>

                <div v-else>
                  <v-list>
                    <v-list-item
                      v-for="user in users.slice(0, 5)"
                      :key="user.id"
                    >
                      <template #prepend>
                        <v-avatar color="grey-lighten-2">
                          <v-icon>mdi-account</v-icon>
                        </v-avatar>
                      </template>

                      <v-list-item-title>
                        {{ user.first_name }} {{ user.last_name }}
                      </v-list-item-title>
                      
                      <v-list-item-subtitle>
                        {{ user.email }} • {{ getRoleLabel(user.system_role) }}
                      </v-list-item-subtitle>

                      <template #append>
                        <v-chip
                          :color="user.is_active ? 'success' : 'error'"
                          size="small"
                          variant="flat"
                        >
                          {{ user.is_active ? 'Aktiv' : 'Inaktiv' }}
                        </v-chip>
                      </template>
                    </v-list-item>
                  </v-list>

                  <v-divider v-if="users.length > 5" class="my-2" />
                  
                  <div v-if="users.length > 5" class="text-center">
                    <v-btn variant="text" @click="openUserManagement">
                      Alle {{ users.length }} Benutzer anzeigen
                      <v-icon class="ml-1">mdi-arrow-right</v-icon>
                    </v-btn>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </div>
        </div>

        <div v-else class="text-center py-8">
          <v-icon size="64" color="grey-lighten-1">mdi-alert-circle</v-icon>
          <p class="text-h6 mt-2">Tenant nicht gefunden</p>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import type { Tenant, TenantUser } from '../types'
import { tenantService } from '../services/tenantService'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'

interface Props {
  data: Tenant | null
}

const props = defineProps<Props>()

const rsdStore = useRSDStore()

// State
const tenant = ref<Tenant | null>(props.data)
const users = ref<TenantUser[]>([])
const loading = ref(false)
const loadingUsers = ref(false)

// Methods
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getRoleLabel = (role: string) => {
  const roleMap: Record<string, string> = {
    'global_admin': 'Global Admin',
    'tenant_admin': 'Tenant Admin',
    'tenant_member': 'Tenant Member'
  }
  return roleMap[role] || role
}

const openUserManagement = () => {
  // This would open a dedicated user management interface
  // For now, we'll show a placeholder
  console.log('Opening user management for tenant:', tenant.value?.id)
}

const loadTenantUsers = async () => {
  if (!tenant.value) return

  loadingUsers.value = true
  try {
    const response = await tenantService.getTenantUsers(tenant.value.id)
    users.value = response.users
  } catch (error) {
    console.error('Error loading tenant users:', error)
  } finally {
    loadingUsers.value = false
  }
}

onMounted(() => {
  if (tenant.value) {
    loadTenantUsers()
  }
})
</script>

<style scoped>
.tenant-view {
  max-width: 100%;
}

.v-chip {
  font-weight: 500;
}

.v-list-item {
  min-height: 56px;
}

.v-card-title {
  font-size: 1rem !important;
  font-weight: 600;
}
</style>