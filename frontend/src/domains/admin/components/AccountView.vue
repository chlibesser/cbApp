<template>
  <div class="account-view">
    <v-card>
      <v-card-text>
        <div v-if="loading" class="text-center py-8">
          <v-progress-circular indeterminate />
          <p class="mt-2">Account-Details werden geladen...</p>
        </div>

        <div v-else-if="account">
          <!-- Account Header -->
          <div class="d-flex align-center mb-6">
            <v-avatar size="48" color="primary" class="mr-3">
              <v-icon color="white">mdi-account</v-icon>
            </v-avatar>
            <div>
              <h2 class="text-h5 font-weight-bold">{{ account.username }}</h2>
              <p class="text-body-2 text-medium-emphasis mb-0">{{ account.email }}</p>
            </div>
          </div>

          <!-- Account Details -->
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
                      <v-list-item-title>Benutzername</v-list-item-title>
                      <v-list-item-subtitle>{{ account.username }}</v-list-item-subtitle>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>E-Mail</v-list-item-title>
                      <v-list-item-subtitle>{{ account.email }}</v-list-item-subtitle>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>System-Rolle</v-list-item-title>
                      <v-list-item-subtitle>
                        <v-chip 
                          :color="getSystemRoleColor(account.system_role)"
                          size="small"
                          variant="flat"
                        >
                          {{ getSystemRoleLabel(account.system_role) }}
                        </v-chip>
                      </v-list-item-subtitle>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>Status</v-list-item-title>
                      <v-list-item-subtitle>
                        <v-chip 
                          :color="account.is_active ? 'success' : 'error'"
                          size="small"
                          variant="flat"
                        >
                          <v-icon size="small" class="mr-1">
                            {{ account.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}
                          </v-icon>
                          {{ account.is_active ? 'Aktiv' : 'Gesperrt' }}
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
                  <v-icon class="mr-2">mdi-clock</v-icon>
                  Zeitstempel
                </v-card-title>
                <v-card-text>
                  <v-list lines="two">
                    <v-list-item>
                      <v-list-item-title>Erstellt am</v-list-item-title>
                      <v-list-item-subtitle>{{ formatDate(account.created_at) }}</v-list-item-subtitle>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>Zuletzt aktualisiert</v-list-item-title>
                      <v-list-item-subtitle>{{ formatDate(account.updated_at) }}</v-list-item-subtitle>
                    </v-list-item>
                    
                    <v-list-item v-if="account.email_verified_at">
                      <v-list-item-title>E-Mail verifiziert</v-list-item-title>
                      <v-list-item-subtitle>{{ formatDate(account.email_verified_at) }}</v-list-item-subtitle>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Associated Tenants Section -->
          <div class="mt-6">
            <h3 class="text-h6 mb-4">Zugewiesene Tenants</h3>

            <v-card variant="outlined">
              <v-card-text>
                <div v-if="!account.tenants || account.tenants.length === 0" class="text-center py-8">
                  <v-icon size="64" color="grey-lighten-1">mdi-office-building-off</v-icon>
                  <p class="text-h6 mt-2">Keine Tenants zugewiesen</p>
                  <p class="text-body-2 text-medium-emphasis">
                    Dieser Account ist noch keinem Tenant zugewiesen.
                  </p>
                </div>

                <v-list v-else>
                  <v-list-item
                    v-for="tenant in account.tenants"
                    :key="tenant.id"
                  >
                    <template #prepend>
                      <v-avatar color="grey-lighten-2">
                        <v-icon>{{ tenant.is_personal ? 'mdi-account' : 'mdi-office-building' }}</v-icon>
                      </v-avatar>
                    </template>

                    <v-list-item-title>
                      {{ tenant.name }}
                    </v-list-item-title>
                    
                    <v-list-item-subtitle>
                      {{ tenant.slug }}
                    </v-list-item-subtitle>

                    <template #append>
                      <v-chip
                        :color="tenant.is_personal ? 'info' : 'primary'"
                        size="small"
                        variant="flat"
                      >
                        {{ tenant.is_personal ? 'Persönlich' : 'Unternehmen' }}
                      </v-chip>
                    </template>
                  </v-list-item>
                </v-list>
              </v-card-text>
            </v-card>
          </div>
        </div>

        <div v-else class="text-center py-8">
          <v-icon size="64" color="grey-lighten-1">mdi-alert-circle</v-icon>
          <p class="text-h6 mt-2">Account nicht gefunden</p>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { Account } from '../types'
import { accountService } from '../services/accountService'

interface Props {
  data: Account | null
}

const props = defineProps<Props>()

// State
const account = ref<Account | null>(props.data)
const loading = ref(false)

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

const getSystemRoleLabel = (role: string | null) => {
  if (!role) return 'Keine Rolle'
  const roleMap: Record<string, string> = {
    'admin': 'Administrator',
    'tenant_admin': 'Tenant Administrator',
    'member': 'Mitglied'
  }
  return roleMap[role] || role
}

const getSystemRoleColor = (role: string | null) => {
  if (!role) return 'grey'
  const colorMap: Record<string, string> = {
    'admin': 'error',
    'tenant_admin': 'warning',
    'member': 'info'
  }
  return colorMap[role] || 'grey'
}

onMounted(async () => {
  if (!account.value && props.data?.id) {
    loading.value = true
    try {
      account.value = await accountService.getAccount(props.data.id)
    } catch (error) {
      console.error('Error loading account:', error)
    } finally {
      loading.value = false
    }
  }
})
</script>

<style scoped>
.account-view {
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