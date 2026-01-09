<template>
  <div class="tenants-view">
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h1 class="text-h4 font-weight-bold">{{$t('admin.tenants.page_title')}}</h1>
        <p class="text-subtitle-1 text-medium-emphasis">
          {{$t('admin.tenants.page_description')}}
        </p>
      </div>
      
      <v-chip 
        v-if="totalCount !== undefined" 
        color="primary" 
        variant="tonal"
        size="large"
        class="px-4"
      >
        {{ totalCount }} {{ $t('admin.tenants.counter_text') }}
      </v-chip>
    </div>

    <!-- Advanced Data Table -->
    <v-card>
      <AdvancedDataTable
        :columns="tenantEntityConfig.fields"
        :api-endpoint="tenantEntityConfig.apiEndpoint"
        enable-create
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
            {{ item.is_active ? $t('admin.tenants.status.active') : $t('admin.tenants.status.inactive') }}
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
              <v-tooltip activator="parent">{{ $t('admin.tenants.tooltips.view') }}</v-tooltip>
            </v-btn>
            
            <v-btn 
              icon 
              size="small" 
              variant="text"
              @click="editTenant(item)"
            >
              <v-icon size="18">mdi-pencil</v-icon>
              <v-tooltip activator="parent">{{ $t('admin.tenants.tooltips.edit') }}</v-tooltip>
            </v-btn>
            
            <v-btn 
              icon 
              size="small" 
              variant="text"
              @click="switchToTenant(item)"
              color="primary"
            >
              <v-icon size="18">mdi-swap-horizontal</v-icon>
              <v-tooltip activator="parent">{{ $t('admin.tenants.tooltips.switch') }}</v-tooltip>
            </v-btn>
            
            <v-btn 
              icon 
              size="small" 
              variant="text"
              color="error"
              @click="deleteTenant(item)"
            >
              <v-icon size="18">mdi-delete</v-icon>
              <v-tooltip activator="parent">{{ $t('admin.tenants.tooltips.delete') }}</v-tooltip>
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
import { useI18n } from 'vue-i18n'
import { AdvancedDataTable } from '@/shared/components'
import { tenantEntityConfig } from '@/config/entities'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'
import { useTenantStore } from '@/infrastructure/stores/tenantStore'
import { tenantService } from '../services/tenantService'
import type { Tenant } from '../types'

const { t } = useI18n()

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
    layoutStore.showSuccess(t('admin.tenants.messages.switch_success', { name: item.name }))
    router.push('/dashboard')
  } catch (error: any) {
    layoutStore.showError(error.response?.data?.message || t('admin.tenants.messages.switch_error'))
  }
}

async function deleteTenant(item: Tenant) {
  const confirmed = await new Promise<boolean>(resolve => {
    // Using browser confirm for now - can be replaced with custom dialog
    resolve(confirm(t('admin.tenants.messages.delete_confirm', { name: item.name })))
  })

  if (!confirmed) return

  try {
    await tenantService.deleteTenant(item.id)
    layoutStore.showSuccess(t('admin.tenants.messages.delete_success'))

    // Trigger table refresh
    window.dispatchEvent(new CustomEvent('refresh-tables'))
  } catch (error: any) {
    layoutStore.showError(error.response?.data?.message || t('admin.tenants.messages.delete_error'))
  }
}
</script>
