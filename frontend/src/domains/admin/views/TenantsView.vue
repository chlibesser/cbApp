<template>
  <div class="tenants-view">
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h1 class="text-h4 font-weight-bold">Tenant-Verwaltung</h1>
        <p class="text-subtitle-1 text-medium-emphasis">
          Verwalten Sie alle System-Tenants
        </p>
      </div>
      
      <v-chip 
        v-if="totalCount !== undefined" 
        color="primary" 
        variant="tonal"
        size="large"
        class="px-4"
      >
        {{ totalCount }} Tenants
      </v-chip>
    </div>

    <!-- Advanced Data Table -->
    <v-card>
      <AdvancedDataTable
        :entity-config="tenantEntityConfig"
        @item-selected="handleItemSelected"
        @item-double-click="viewTenant"
        @create="createTenant"
        @update:count="handleCountUpdate"
      >
        <!-- Custom slot for name with link -->
        <template #item.name="{ item }">
          <div>
            <div class="font-weight-medium">{{ item.name }}</div>
            <div class="text-caption text-medium-emphasis">{{ item.slug }}</div>
          </div>
        </template>

        <!-- Custom slot for status -->
        <template #item.is_active="{ item }">
          <v-chip 
            :color="item.is_active ? 'success' : 'error'"
            variant="tonal"
            size="x-small"
          >
            {{ item.is_active ? 'Aktiv' : 'Inaktiv' }}
          </v-chip>
        </template>

        <!-- Custom slot for user count -->
        <template #item.current_users_count="{ item }">
          <div class="text-center">
            <span class="font-weight-medium">{{ item.current_users_count || 0 }}</span>
            <span class="text-caption text-medium-emphasis">
              / {{ item.max_users || '∞' }}
            </span>
          </div>
        </template>

        <!-- Custom slot for actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <v-btn 
              icon 
              size="small" 
              variant="text"
              @click="viewTenant(item)"
            >
              <v-icon size="18">mdi-eye</v-icon>
              <v-tooltip activator="parent">Ansehen</v-tooltip>
            </v-btn>
            
            <v-btn 
              icon 
              size="small" 
              variant="text"
              @click="editTenant(item)"
            >
              <v-icon size="18">mdi-pencil</v-icon>
              <v-tooltip activator="parent">Bearbeiten</v-tooltip>
            </v-btn>
            
            <v-btn 
              icon 
              size="small" 
              variant="text"
              @click="switchToTenant(item)"
              color="primary"
            >
              <v-icon size="18">mdi-swap-horizontal</v-icon>
              <v-tooltip activator="parent">Zu Tenant wechseln</v-tooltip>
            </v-btn>
            
            <v-btn 
              icon 
              size="small" 
              variant="text"
              color="error"
              @click="deleteTenant(item)"
            >
              <v-icon size="18">mdi-delete</v-icon>
              <v-tooltip activator="parent">Löschen</v-tooltip>
            </v-btn>
          </div>
        </template>
      </AdvancedDataTable>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { AdvancedDataTable } from '@/shared/components'
import { tenantEntityConfig } from '@/config/entities'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'
import { useTenantStore } from '@/infrastructure/stores/tenantStore'
import { tenantService } from '../services/tenantService'
import type { Tenant } from '../types'

const router = useRouter()
const rsdStore = useRSDStore()
const layoutStore = useLayoutStore()
const tenantStore = useTenantStore()
const totalCount = ref<number>()

function handleItemSelected(item: Tenant) {
  console.log('Selected tenant:', item)
}

function handleCountUpdate(count: number) {
  totalCount.value = count
}

function viewTenant(item: Tenant) {
  router.push(`/admin/tenants/${item.id}`)
}

function editTenant(item: Tenant) {
  rsdStore.open('tenant', 'edit', item)
}

function createTenant() {
  rsdStore.open('tenant', 'create')
}

async function switchToTenant(item: Tenant) {
  try {
    await tenantStore.switchTenant(item.id)
    layoutStore.showSuccess(`Zu Tenant "${item.name}" gewechselt`)
    router.push('/dashboard')
  } catch (error: any) {
    layoutStore.showError(error.response?.data?.message || 'Fehler beim Wechseln des Tenants')
  }
}

async function deleteTenant(item: Tenant) {
  const confirmed = await new Promise<boolean>(resolve => {
    // Using browser confirm for now - can be replaced with custom dialog
    resolve(confirm(`Möchten Sie den Tenant "${item.name}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.`))
  })
  
  if (!confirmed) return

  try {
    await tenantService.deleteTenant(item.id)
    layoutStore.showSuccess('Tenant wurde gelöscht')
    
    // Trigger table refresh
    window.dispatchEvent(new CustomEvent('refresh-tables'))
  } catch (error: any) {
    layoutStore.showError(error.response?.data?.message || 'Fehler beim Löschen des Tenants')
  }
}
</script>
