<template>
  <div class="view-container">
    <!-- Toolbar -->
    <v-toolbar flat color="primary" variant="outlined" rounded density="compact" class="view-toolbar">
      <v-toolbar-title class="text-h5 font-weight-bold view-title">
        {{ $t('admin.accounts.page_title') }}
      </v-toolbar-title>
      <v-spacer />
      <v-btn color="primary" @click="createAccount">
        <v-icon start>mdi-plus</v-icon>
        {{ $t('common.actions.create') }}
      </v-btn>
    </v-toolbar>

    <!-- Scrollable Content -->
    <div class="view-content">
      <AdvancedDataTable
        :columns="accountEntityConfig.fields"
        :api-endpoint="accountEntityConfig.apiEndpoint"
        table-key="admin.accounts"
        @item-selected="handleItemSelected"
        @item-double-click="viewAccount"
        @update:count="handleCountUpdate"
      >
        <!-- Custom slot for email verification status -->
        <template #item.email_verified_at="{ item }">
          <v-chip 
            :color="item.email_verified_at ? 'success' : 'warning'"
            variant="tonal"
            size="x-small"
          >
            {{ item.email_verified_at ? $t('admin.accounts.verification_status.verified') : $t('admin.accounts.verification_status.not_verified') }}
          </v-chip>
        </template>

        <!-- Custom slot for system role -->
        <template #item.system_role="{ item }">
          <v-chip
            :color="getSystemRoleColor(item.system_role)"
            variant="tonal"
            size="x-small"
          >
            {{ getSystemRoleLabel(item.system_role) }}
          </v-chip>
        </template>

        <!-- Custom slot for actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <v-btn 
              icon 
              size="small" 
              variant="text"
              @click="viewAccount(item)"
            >
              <v-icon size="18">mdi-eye</v-icon>
              <v-tooltip activator="parent">{{ $t('admin.accounts.tooltips.view') }}</v-tooltip>
            </v-btn>
            
            <v-btn 
              icon 
              size="small" 
              variant="text"
              @click="editAccount(item)"
            >
              <v-icon size="18">mdi-pencil</v-icon>
              <v-tooltip activator="parent">{{ $t('admin.accounts.tooltips.edit') }}</v-tooltip>
            </v-btn>
            
            <v-btn 
              icon 
              size="small" 
              variant="text"
              color="error"
              @click="deleteAccount(item)"
            >
              <v-icon size="18">mdi-delete</v-icon>
              <v-tooltip activator="parent">{{ $t('admin.accounts.tooltips.delete') }}</v-tooltip>
            </v-btn>
          </div>
        </template>
      </AdvancedDataTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { AdvancedDataTable } from '@/shared/components'
import { accountEntityConfig } from '@/config/entities'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'
import { accountService } from '../services/accountService'
import type { Account } from '../types'

const { t } = useI18n()

const rsdStore = useRSDStore()
const layoutStore = useLayoutStore()
const totalCount = ref<number>()

function handleItemSelected(item: Account) {
  console.log('Selected account:', item)
}

function handleCountUpdate(count: number) {
  totalCount.value = count
}

function viewAccount(item: Account) {
  rsdStore.open('account', 'view', item)
}

function editAccount(item: Account) {
  rsdStore.open('account', 'edit', item)
}

function createAccount() {
  rsdStore.open('account', 'create')
}

async function deleteAccount(item: Account) {
  const confirmed = await new Promise<boolean>(resolve => {
    // Using browser confirm for now - can be replaced with custom dialog
    resolve(confirm(t('admin.accounts.messages.delete_confirm', { email: item.email })))
  })

  if (!confirmed) return

  try {
    await accountService.deleteAccount(item.id)
    layoutStore.showSuccess(t('admin.accounts.messages.delete_success'))

    // Trigger table refresh
    window.dispatchEvent(new CustomEvent('refresh-tables'))
  } catch (error: any) {
    layoutStore.showError(error.response?.data?.message || t('admin.accounts.messages.delete_error'))
  }
}

function getSystemRoleColor(role: string): string {
  const colors: Record<string, string> = {
    global_admin: 'error',
    tenant_admin: 'warning', 
    tenant_member: 'primary'
  }
  return colors[role] || 'default'
}

function getSystemRoleLabel(role: string): string {
  const labels: Record<string, string> = {
    global_admin: t('admin.accounts.roles.global_admin'),
    tenant_admin: t('admin.accounts.roles.tenant_admin'),
    tenant_member: t('admin.accounts.roles.member')
  }
  return labels[role] || role
}
</script>

<style scoped>
.view-container {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
}

.view-toolbar {
  flex-shrink: 0;
  background-color: rgba(25, 118, 210, 0.05) !important;
  border: 2px solid rgba(25, 118, 210, 0.8) !important;
}

.view-content {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  margin-top: 8px;
  padding: 16px;
  border: 2px solid rgba(25, 118, 210, 0.8);
  border-radius: 4px;
  background-color: rgba(25, 118, 210, 0.02);
}

.view-title {
  color: rgba(25, 118, 210, 0.8) !important;
}
</style>
