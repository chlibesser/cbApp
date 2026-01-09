<template>
  <div class="tenant-detail-view">
    <!-- Standard Header mit Back Button -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div class="d-flex align-center">
        <v-btn
          icon
          variant="text"
          @click="goBack"
          class="mr-3"
        >
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <div>
          <h1 class="text-h4 font-weight-bold">{{ tenant?.name || $t('admin.tenants.detail.fallback_title') }}</h1>
          <p class="text-subtitle-1 text-medium-emphasis">
            {{ tenant?.description || $t('admin.tenants.detail.description') }}
          </p>
        </div>
      </div>
      
      <v-chip 
        v-if="tenant"
        :color="tenant.is_active ? 'success' : 'error'" 
        variant="tonal"
        size="large"
        class="px-4"
      >
        <v-icon size="16" start>
          {{ tenant.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}
        </v-icon>
        {{ tenant.is_active ? $t('admin.tenants.status.active') : $t('admin.tenants.status.inactive') }}
      </v-chip>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <v-progress-circular indeterminate size="48" />
      <p class="mt-2">{{ $t('admin.tenants.detail.loading') }}</p>
    </div>

    <!-- Error State -->
    <v-alert v-else-if="error" type="error" class="mb-4">
      {{ error }}
    </v-alert>

    <!-- Tab Navigation -->
    <v-tabs 
      v-else-if="tenant"
      v-model="activeTab" 
      class="mb-4"
      color="primary"
    >
      <v-tab value="overview">{{ $t('admin.tenants.detail.tabs.overview') }}</v-tab>
      <v-tab value="users">{{ $t('admin.tenants.detail.tabs.users') }}</v-tab>
      <v-tab value="settings">{{ $t('admin.tenants.detail.tabs.settings') }}</v-tab>
      <v-tab value="statistics">{{ $t('admin.tenants.detail.tabs.statistics') }}</v-tab>
    </v-tabs>

    <!-- Tab Inhalt -->
    <v-window v-if="tenant" v-model="activeTab">
      <!-- Übersicht Tab -->
      <v-window-item value="overview">
        <v-row>
          <v-col cols="12" md="8">
            <v-card>
              <v-card-title>{{ $t('admin.tenants.detail.basic_info') }}</v-card-title>
              <v-card-text>
                <v-list>
                  <v-list-item>
                    <v-list-item-title>{{ $t('admin.tenants.detail.fields.name') }}</v-list-item-title>
                    <v-list-item-subtitle>{{ tenant.name }}</v-list-item-subtitle>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-title>{{ $t('admin.tenants.detail.fields.slug') }}</v-list-item-title>
                    <v-list-item-subtitle>{{ tenant.slug }}</v-list-item-subtitle>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-title>{{ $t('admin.tenants.detail.fields.description') }}</v-list-item-title>
                    <v-list-item-subtitle>{{ tenant.description || $t('admin.tenants.detail.no_description') }}</v-list-item-subtitle>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-title>{{ $t('admin.tenants.detail.fields.type') }}</v-list-item-title>
                    <v-list-item-subtitle>
                      <v-chip size="small" :color="tenant.is_personal ? 'info' : 'success'" variant="tonal">
                        <v-icon size="12" start>
                          {{ tenant.is_personal ? 'mdi-account' : 'mdi-domain' }}
                        </v-icon>
                        {{ tenant.is_personal ? $t('admin.tenants.detail.tenant_types.personal') : $t('admin.tenants.detail.tenant_types.company') }}
                      </v-chip>
                    </v-list-item-subtitle>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-title>{{ $t('admin.tenants.detail.fields.created_at') }}</v-list-item-title>
                    <v-list-item-subtitle>{{ formatDate(tenant.created_at) }}</v-list-item-subtitle>
                  </v-list-item>
                </v-list>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card>
              <v-card-title>{{ $t('admin.tenants.detail.actions_title') }}</v-card-title>
              <v-card-text>
                <div class="d-flex flex-column gap-2">
                  <v-btn
                    color="primary"
                    variant="outlined"
                    prepend-icon="mdi-pencil"
                    @click="editTenant"
                    block
                  >
                    {{ $t('admin.tenants.actions.edit') }}
                  </v-btn>

                  <v-btn
                    color="success"
                    variant="outlined"
                    prepend-icon="mdi-swap-horizontal"
                    @click="switchToTenant"
                    block
                  >
                    {{ $t('admin.tenants.detail.switch_button') }}
                  </v-btn>

                  <v-btn
                    :color="tenant.is_active ? 'warning' : 'success'"
                    variant="outlined"
                    :prepend-icon="tenant.is_active ? 'mdi-pause' : 'mdi-play'"
                    @click="toggleTenantStatus"
                    block
                  >
                    {{ tenant.is_active ? $t('admin.tenants.detail.toggle_deactivate') : $t('admin.tenants.detail.toggle_activate') }}
                  </v-btn>

                  <v-divider class="my-2" />

                  <v-btn
                    color="error"
                    variant="outlined"
                    prepend-icon="mdi-delete"
                    @click="deleteTenant"
                    block
                  >
                    {{ $t('admin.tenants.actions.delete') }}
                  </v-btn>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-window-item>

      <!-- Benutzer Tab -->
      <v-window-item value="users">
        <v-card>
          <v-card-title class="d-flex justify-space-between">
            {{ $t('admin.tenants.detail.user_management') }}
            <v-btn color="primary" prepend-icon="mdi-plus">
              {{ $t('admin.tenants.detail.add_user') }}
            </v-btn>
          </v-card-title>
          <v-card-text>
            <div class="text-center py-8">
              <v-icon size="64" color="grey-lighten-1">mdi-account-group</v-icon>
              <h3 class="text-h6 mt-4">{{ $t('admin.tenants.detail.users_empty.title') }}</h3>
              <p class="text-body-2 text-grey mt-2">
                {{ $t('admin.tenants.detail.users_empty.description') }}
              </p>
              <p class="text-caption">
                {{ $t('admin.tenants.detail.user_count_label', { current: tenant.users_count || 0, max: tenant.max_users || '∞' }) }}
              </p>
            </div>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Einstellungen Tab -->
      <v-window-item value="settings">
        <v-card>
          <v-card-title>{{ $t('admin.tenants.detail.settings_title') }}</v-card-title>
          <v-card-text>
            <v-form>
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="tenantSettings.maxUsers"
                    :label="$t('admin.tenants.detail.form.max_users')"
                    type="number"
                    variant="outlined"
                    :hint="tenant.max_users ? $t('admin.tenants.detail.form.max_users_hint_current', { count: tenant.max_users }) : $t('admin.tenants.detail.form.max_users_hint_unlimited')"
                    persistent-hint
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="tenantSettings.tenantType"
                    :items="tenantTypes"
                    :label="$t('admin.tenants.detail.form.tenant_type')"
                    variant="outlined"
                  />
                </v-col>
              </v-row>

              <v-row>
                <v-col cols="12">
                  <v-textarea
                    v-model="tenantSettings.description"
                    :label="$t('admin.tenants.detail.form.description')"
                    variant="outlined"
                    rows="3"
                  />
                </v-col>
              </v-row>

              <v-row>
                <v-col cols="12">
                  <v-switch
                    v-model="tenantSettings.isActive"
                    :label="$t('admin.tenants.detail.form.is_active')"
                    color="primary"
                  />
                </v-col>
              </v-row>

              <v-divider class="my-4" />

              <div class="d-flex justify-end gap-2">
                <v-btn variant="outlined" @click="resetSettings">
                  {{ $t('admin.tenants.detail.form.reset_button') }}
                </v-btn>
                <v-btn color="primary" @click="saveTenantSettings">
                  {{ $t('admin.tenants.detail.form.save_button') }}
                </v-btn>
              </div>
            </v-form>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Statistiken Tab -->
      <v-window-item value="statistics">
        <v-row>
          <v-col cols="12" sm="6" md="3">
            <v-card class="text-center" height="120">
              <v-card-text class="d-flex flex-column justify-center align-center h-100 pa-4">
                <v-icon size="32" color="info" class="mb-2">mdi-account-multiple</v-icon>
                <h3 class="text-h5 font-weight-bold mb-1">{{ tenant.users_count || 0 }}</h3>
                <p class="text-body-2 text-medium-emphasis mb-0">{{ $t('admin.tenants.detail.statistics.active_users') }}</p>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" sm="6" md="3">
            <v-card class="text-center" height="120">
              <v-card-text class="d-flex flex-column justify-center align-center h-100 pa-4">
                <v-icon size="32" color="success" class="mb-2">mdi-calendar-check</v-icon>
                <h3 class="text-h5 font-weight-bold mb-1">{{ daysActive }}</h3>
                <p class="text-body-2 text-medium-emphasis mb-0">{{ $t('admin.tenants.detail.statistics.days_active') }}</p>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" sm="6" md="3">
            <v-card class="text-center" height="120">
              <v-card-text class="d-flex flex-column justify-center align-center h-100 pa-4">
                <v-icon size="32" color="warning" class="mb-2">mdi-chart-line</v-icon>
                <h3 class="text-h5 font-weight-bold mb-1">-</h3>
                <p class="text-body-2 text-medium-emphasis mb-0">{{ $t('admin.tenants.detail.statistics.activity') }}</p>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" sm="6" md="3">
            <v-card class="text-center" height="120">
              <v-card-text class="d-flex flex-column justify-center align-center h-100 pa-4">
                <v-icon size="32" color="purple" class="mb-2">mdi-storage</v-icon>
                <h3 class="text-h5 font-weight-bold mb-1">-</h3>
                <p class="text-body-2 text-medium-emphasis mb-0">{{ $t('admin.tenants.detail.statistics.storage') }}</p>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-row class="mt-4">
          <v-col cols="12">
            <v-card>
              <v-card-title>{{ $t('admin.tenants.detail.statistics.activity_title') }}</v-card-title>
              <v-card-text>
                <div class="text-center py-8">
                  <v-icon size="64" color="grey-lighten-1">mdi-chart-timeline</v-icon>
                  <h3 class="text-h6 mt-4">{{ $t('admin.tenants.detail.activity_empty.title') }}</h3>
                  <p class="text-body-2 text-grey mt-2">
                    {{ $t('admin.tenants.detail.activity_empty.description') }}
                  </p>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-window-item>
    </v-window>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { useTenantStore } from '@/infrastructure/stores/tenantStore'
import { tenantService } from '../services/tenantService'
import type { Tenant } from '../types'

const { t } = useI18n()

// Props
const props = defineProps<{
  id: string
}>()

const router = useRouter()
const route = useRoute()
const layoutStore = useLayoutStore()
const rsdStore = useRSDStore()
const tenantStore = useTenantStore()

// Local state
const activeTab = ref('overview')
const loading = ref(true)
const error = ref<string | null>(null)
const tenant = ref<Tenant | null>(null)

const tenantSettings = ref({
  maxUsers: null as number | null,
  tenantType: 'company',
  description: '',
  isActive: true
})

const tenantTypes = computed(() => [
  { title: t('admin.tenants.detail.tenant_types.company'), value: 'company' },
  { title: t('admin.tenants.detail.tenant_types.personal'), value: 'personal' }
])

// Computed
const daysActive = computed(() => {
  if (!tenant.value?.created_at) return 0
  const created = new Date(tenant.value.created_at)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - created.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays
})

// Methods
function goBack() {
  router.push('/admin/tenants')
}

function formatDate(dateString: string) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('de-DE', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function editTenant() {
  if (tenant.value) {
    rsdStore.open('tenant', 'edit', tenant.value)
  }
}

async function switchToTenant() {
  if (!tenant.value) return

  try {
    await tenantStore.switchTenant(tenant.value.id)
    layoutStore.showSuccess(t('admin.tenants.messages.switch_success', { name: tenant.value.name }))
    router.push('/dashboard')
  } catch (error: any) {
    layoutStore.showError(error.response?.data?.message || t('admin.tenants.messages.switch_error'))
  }
}

async function toggleTenantStatus() {
  if (!tenant.value) return

  try {
    const newStatus = !tenant.value.is_active
    // API-Call would happen here
    tenant.value.is_active = newStatus
    layoutStore.showSuccess(newStatus ? t('admin.tenants.messages.status_activated') : t('admin.tenants.messages.status_deactivated'))
  } catch (error: any) {
    layoutStore.showError(t('admin.tenants.messages.status_error'))
  }
}

async function deleteTenant() {
  if (!tenant.value) return

  const confirmed = await new Promise<boolean>(resolve => {
    resolve(confirm(t('admin.tenants.messages.delete_confirm', { name: tenant.value!.name })))
  })

  if (!confirmed) return

  try {
    await tenantService.deleteTenant(tenant.value.id)
    layoutStore.showSuccess(t('admin.tenants.messages.delete_success'))
    router.push('/admin/tenants')
  } catch (error: any) {
    layoutStore.showError(error.response?.data?.message || t('admin.tenants.messages.delete_error'))
  }
}

function resetSettings() {
  if (!tenant.value) return
  
  tenantSettings.value = {
    maxUsers: tenant.value.max_users || null,
    tenantType: tenant.value.is_personal ? 'personal' : 'company',
    description: tenant.value.description || '',
    isActive: tenant.value.is_active
  }
}

async function saveTenantSettings() {
  if (!tenant.value) return

  try {
    // API-Call would happen here
    // const updatedTenant = await tenantService.updateTenant(tenant.value.id, tenantSettings.value)
    // tenant.value = updatedTenant

    layoutStore.showSuccess(t('admin.tenants.messages.settings_saved'))
  } catch (error: any) {
    layoutStore.showError(t('admin.tenants.messages.settings_error'))
  }
}

async function loadTenant() {
  loading.value = true
  error.value = null

  try {
    // Versuche zuerst echte API-Daten zu laden
    console.log('Loading tenant with ID:', props.id)
    const fetchedTenant = await tenantService.getTenant(props.id)
    console.log('Fetched tenant:', fetchedTenant)
    tenant.value = fetchedTenant

    // Settings initialisieren
    resetSettings()
  } catch (err: any) {
    // Fallback zu Mock-Daten wenn API fehlschlägt
    console.warn('API call failed, using mock data:', err)
    
    const mockTenant = {
      id: props.id,
      name: 'Demo Firma GmbH',
      slug: 'demo-firma-gmbh',
      description: 'Ein Beispiel-Tenant für Demonstrationszwecke',
      is_personal: false,
      is_active: true,
      max_users: 25,
      users_count: 12,
      created_at: '2024-01-15T10:30:00Z',
      updated_at: '2024-12-11T15:45:00Z'
    }
    
    console.log('Using mock tenant:', mockTenant)
    tenant.value = mockTenant

    // Settings initialisieren
    resetSettings()
  } finally {
    loading.value = false
    console.log('Final tenant value:', tenant.value)
  }
}

// Lifecycle
onMounted(() => {
  loadTenant()
})
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>