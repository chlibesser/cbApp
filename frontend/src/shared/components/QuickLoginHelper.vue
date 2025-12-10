<template>
  <v-card v-if="isDevelopment" class="ma-4" variant="outlined" data-testid="quick-login-helper">
    <v-card-title class="text-h6 d-flex align-center">
      <v-icon class="me-2">mdi-account-multiple</v-icon>
      Quick Login (Development)
    </v-card-title>
    
    <v-card-text>
      <v-row data-testid="quick-login-accounts">
        <v-col
          v-for="account in quickLogins"
          :key="account.username"
          cols="12"
          sm="6"
          md="4"
        >
          <v-card variant="outlined" class="mb-2" :data-testid="`account-card-${account.username}`">
            <v-card-title class="text-subtitle-1 d-flex align-center" :data-testid="`account-info-${account.username}`">
              <v-icon :icon="getSystemRoleIcon(account.system_role)" :color="getSystemRoleColor(account.system_role)" class="me-2" size="small"></v-icon>
              {{ account.username }}
              <v-chip 
                :color="getSystemRoleColor(account.system_role)" 
                variant="tonal" 
                size="x-small" 
                class="ml-auto"
                :data-testid="`account-role-${account.username}`"
              >
                {{ getSystemRoleLabel(account.system_role) }}
              </v-chip>
            </v-card-title>
            <v-card-subtitle class="text-caption">
              {{ account.email }}
              <br>
              <span class="text-body-2 font-weight-medium" :style="{ color: getSystemRoleColor(account.system_role) }" :data-testid="`system-role-${account.username}`">
                {{ getSystemRoleDescription(account.system_role) }}
              </span>
            </v-card-subtitle>
            
            <v-card-text v-if="account.tenants.length > 0">
              <div class="text-caption mb-2">Available Tenants:</div>
              <v-chip-group column>
                <v-chip
                  v-for="tenant in account.tenants"
                  :key="tenant.id"
                  size="small"
                  :variant="getChipVariant(tenant.role?.name)"
                  :color="getChipColor(tenant.role?.name)"
                  @click="handleQuickLogin(account.username, tenant.id)"
                  :loading="loading"
                  class="mb-1"
                >
                  <v-icon start size="small">
                    {{ getRoleIcon(tenant.role?.name) }}
                  </v-icon>
                  <div class="d-flex flex-column align-start">
                    <span class="text-subtitle-2">{{ getTenantDisplayName(tenant) }}</span>
                    <span class="text-caption">{{ getRoleDisplayName(tenant.role?.name) }}</span>
                  </div>
                  <v-tooltip activator="parent" location="bottom">
                    <div>
                      <strong>{{ getTenantTypeLabel(tenant) }}:</strong> {{ tenant.name }}<br>
                      <strong>Role:</strong> {{ getRoleDisplayName(tenant.role?.name) }}<br>
                      <strong>Type:</strong> {{ getTenantTypeDescription(tenant) }}<br>
                      <strong>Access:</strong> {{ getRoleDescription(tenant.role?.name) }}
                    </div>
                  </v-tooltip>
                </v-chip>
              </v-chip-group>
            </v-card-text>
            
            <v-card-actions v-else>
              <v-btn
                size="small"
                variant="outlined"
                @click="handleQuickLogin(account.username)"
                :loading="loading"
                :data-testid="`quick-login-${account.username}`"
              >
                Login (No Tenant)
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
      
      <v-alert v-if="error" type="error" class="mt-3" data-testid="quick-login-error">
        {{ error }}
      </v-alert>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '../../infrastructure/stores/authStore'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const loading = ref(false)
const error = ref('')

const isDevelopment = computed(() => import.meta.env.DEV)
const quickLogins = computed(() => authStore.availableQuickLogins)

const handleQuickLogin = async (username: string, tenantId?: number) => {
  try {
    loading.value = true
    error.value = ''
    
    await authStore.quickLogin(username, tenantId)
    
    // Redirect to dashboard or appropriate page
    router.push('/')
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || 'Quick login failed'
  } finally {
    loading.value = false
  }
}

// Role display helpers
const getRoleDisplayName = (roleName?: string): string => {
  if (!roleName) return 'No Role'
  
  const roleMap: Record<string, string> = {
    'Owner': 'Owner',
    'Admin': 'Admin',
    'Buchhalter (Full)': 'Buchhalter (Full)',
    'Buchhalter (Read-Only)': 'Buchhalter (Read-Only)', 
    'User': 'User'
  }
  
  return roleMap[roleName] || roleName
}

const getRoleIcon = (roleName?: string): string => {
  if (!roleName) return 'mdi-account-question'
  
  const iconMap: Record<string, string> = {
    'Owner': 'mdi-crown',
    'Admin': 'mdi-shield-account',
    'Buchhalter (Full)': 'mdi-calculator',
    'Buchhalter (Read-Only)': 'mdi-calculator-variant',
    'User': 'mdi-account'
  }
  
  return iconMap[roleName] || 'mdi-account'
}

const getChipColor = (roleName?: string): string => {
  if (!roleName) return 'default'
  
  const colorMap: Record<string, string> = {
    'Owner': 'purple',
    'Admin': 'blue',
    'Buchhalter (Full)': 'green',
    'Buchhalter (Read-Only)': 'orange',
    'User': 'teal'
  }
  
  return colorMap[roleName] || 'default'
}

const getChipVariant = (roleName?: string): string => {
  if (!roleName) return 'outlined'
  
  // Owner bekommt 'flat' für bessere Sichtbarkeit
  if (roleName === 'Owner') {
    return 'flat'
  }
  
  return 'tonal'
}

const getRoleDescription = (roleName?: string): string => {
  if (!roleName) return 'No permissions'
  
  const descMap: Record<string, string> = {
    'Owner': 'Vollzugriff auf alles (Personal Tenant)',
    'Admin': 'Administrator mit fast allen Rechten',
    'Buchhalter (Full)': 'Vollzugriff auf Buchhaltung',
    'Buchhalter (Read-Only)': 'Nur Lesezugriff auf Buchhaltung',
    'User': 'Basis-Benutzer Zugriff'
  }
  
  return descMap[roleName] || 'Standard permissions'
}

// System Role helpers
const getSystemRoleLabel = (systemRole?: string): string => {
  if (!systemRole) return 'No Role'
  
  const roleMap: Record<string, string> = {
    'admin': 'Administrator',
    'tenant_admin': 'Tenant Admin',
    'member': 'Mitglied'
  }
  
  return roleMap[systemRole] || systemRole
}

const getSystemRoleIcon = (systemRole?: string): string => {
  if (!systemRole) return 'mdi-account-question'
  
  const iconMap: Record<string, string> = {
    'admin': 'mdi-shield-crown',
    'tenant_admin': 'mdi-shield-account',
    'member': 'mdi-account'
  }
  
  return iconMap[systemRole] || 'mdi-account'
}

const getSystemRoleColor = (systemRole?: string): string => {
  if (!systemRole) return 'grey'
  
  const colorMap: Record<string, string> = {
    'admin': 'red',
    'tenant_admin': 'blue',
    'member': 'green'
  }
  
  return colorMap[systemRole] || 'grey'
}

const getSystemRoleDescription = (systemRole?: string): string => {
  if (!systemRole) return 'Keine System-Berechtigung'
  
  const descMap: Record<string, string> = {
    'admin': 'Vollzugriff auf alle Tenants und System-Einstellungen',
    'tenant_admin': 'Verwaltung des eigenen Tenants (User, Kategorien, Inhalte)',
    'member': 'Standard-Benutzer mit Basis-Zugriffsrechten'
  }
  
  return descMap[systemRole] || 'Standard-Berechtigung'
}

// Tenant Type helpers
const getTenantDisplayName = (tenant: any): string => {
  if (tenant.is_personal) {
    return tenant.name.replace("'s Workspace", "") + " (Personal)"
  }
  return tenant.name
}

const getTenantTypeLabel = (tenant: any): string => {
  return tenant.is_personal ? 'Personal Workspace' : 'Firma'
}

const getTenantTypeDescription = (tenant: any): string => {
  return tenant.is_personal ? 'Persönlicher Arbeitsbereich' : 'Firmen-Mandant'
}

onMounted(async () => {
  if (isDevelopment.value) {
    await authStore.loadQuickLogins()
  }
})
</script>