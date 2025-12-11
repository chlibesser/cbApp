<template>
  <div class="accounts-view">
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h1 class="text-h4 font-weight-bold">Account-Verwaltung</h1>
        <p class="text-subtitle-1 text-medium-emphasis">
          Verwalten Sie alle System-Accounts
        </p>
      </div>
      
      <v-chip 
        v-if="totalCount !== undefined" 
        color="primary" 
        variant="tonal"
        size="large"
        class="px-4"
      >
        {{ totalCount }} Accounts
      </v-chip>
    </div>

    <!-- Advanced Data Table -->
    <v-card>
      <AdvancedDataTable
        :entity-config="accountEntityConfig"
        @item-selected="handleItemSelected"
        @item-double-click="viewAccount"
        @create="createAccount"
        @update:count="handleCountUpdate"
      >
        <!-- Custom slot for email verification status -->
        <template #item.email_verified_at="{ item }">
          <v-chip 
            :color="item.email_verified_at ? 'success' : 'warning'"
            variant="tonal"
            size="x-small"
          >
            {{ item.email_verified_at ? 'Verifiziert' : 'Nicht verifiziert' }}
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
              <v-tooltip activator="parent">Ansehen</v-tooltip>
            </v-btn>
            
            <v-btn 
              icon 
              size="small" 
              variant="text"
              @click="editAccount(item)"
            >
              <v-icon size="18">mdi-pencil</v-icon>
              <v-tooltip activator="parent">Bearbeiten</v-tooltip>
            </v-btn>
            
            <v-btn 
              icon 
              size="small" 
              variant="text"
              color="error"
              @click="deleteAccount(item)"
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
import { AdvancedDataTable } from '@/shared/components'
import { accountEntityConfig } from '@/config/entities'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'
import { accountService } from '../services/accountService'
import type { Account } from '../types'

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
    resolve(confirm(`Möchten Sie den Account "${item.email}" wirklich löschen?`))
  })
  
  if (!confirmed) return

  try {
    await accountService.deleteAccount(item.id)
    layoutStore.showSuccess('Account wurde gelöscht')
    
    // Trigger table refresh
    window.dispatchEvent(new CustomEvent('refresh-tables'))
  } catch (error: any) {
    layoutStore.showError(error.response?.data?.message || 'Fehler beim Löschen des Accounts')
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
    global_admin: 'Global Admin',
    tenant_admin: 'Tenant Admin',
    tenant_member: 'Mitglied'
  }
  return labels[role] || role
}
</script>
