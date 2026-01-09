<template>
  <div class="account-rsd">
    <!-- View Mode -->
    <v-container v-if="rsdStore.isViewMode" class="pa-4 pl-2 pr-4">
      <v-row>
        <v-col cols="12">
          <h3 class="text-h6 mb-4">{{ $t('admin.accounts.components.view.general_info') }}</h3>
        </v-col>

        <v-col cols="12">
          <div class="text-caption text-grey mb-1">{{ $t('admin.accounts.fields.email') }}</div>
          <div class="text-body-1">{{ rsdStore.data?.email || '-' }}</div>
        </v-col>

        <v-col cols="12">
          <div class="text-caption text-grey mb-1">{{ $t('admin.accounts.fields.system_role') }}</div>
          <v-chip
            :color="getSystemRoleColor(rsdStore.data?.system_role)"
            variant="tonal"
            size="small"
          >
            {{ getSystemRoleLabel(rsdStore.data?.system_role) }}
          </v-chip>
        </v-col>

        <v-col cols="12">
          <div class="text-caption text-grey mb-1">{{ $t('admin.accounts.fields.status') }}</div>
          <v-chip
            :color="rsdStore.data?.email_verified_at ? 'success' : 'warning'"
            variant="tonal"
            size="small"
          >
            <v-icon size="small" class="mr-1">
              {{ rsdStore.data?.email_verified_at ? 'mdi-check-circle' : 'mdi-clock-outline' }}
            </v-icon>
            {{ rsdStore.data?.email_verified_at ? $t('admin.accounts.verification_status.verified') : $t('admin.accounts.verification_status.not_verified') }}
          </v-chip>
        </v-col>

        <v-col cols="12">
          <div class="text-caption text-grey mb-1">{{ $t('admin.accounts.components.view.created_label') }}</div>
          <div class="text-body-1">{{ formatDateTime(rsdStore.data?.created_at) }}</div>
        </v-col>

        <v-col cols="12">
          <div class="text-caption text-grey mb-1">{{ $t('admin.accounts.components.view.updated_label') }}</div>
          <div class="text-body-1">{{ formatDateTime(rsdStore.data?.updated_at) }}</div>
        </v-col>
      </v-row>
    </v-container>

    <!-- Edit/Create Mode -->
    <v-container v-else class="pa-4 pl-2 pr-4">
      <v-row>
        <v-col cols="12">
          <h3 class="text-h6 mb-4">
            {{ rsdStore.isCreateMode ? $t('admin.accounts.components.create.title') : $t('admin.accounts.components.edit.title') }}
          </h3>
        </v-col>

        <v-col cols="12">
          <v-text-field
            v-model="form.email"
            :label="$t('admin.accounts.components.edit.fields.email') + ' *'"
            variant="outlined"
            density="compact"
            type="email"
            :error-messages="errors.email"
          />
        </v-col>

        <v-col cols="12" v-if="rsdStore.isCreateMode">
          <v-text-field
            v-model="form.password"
            :label="$t('admin.accounts.components.create.fields.password') + ' *'"
            variant="outlined"
            density="compact"
            type="password"
            :error-messages="errors.password"
          />
        </v-col>

        <v-col cols="12">
          <v-select
            v-model="form.system_role"
            :label="$t('admin.accounts.components.edit.fields.system_role') + ' *'"
            variant="outlined"
            density="compact"
            :items="systemRoles"
            item-title="text"
            item-value="value"
            :error-messages="errors.system_role"
          />
        </v-col>

        <v-col cols="12" class="pt-6">
          <div class="d-flex gap-2">
            <v-btn
              color="primary"
              variant="flat"
              :loading="loading"
              @click="handleSave"
            >
              {{ rsdStore.isCreateMode ? $t('admin.accounts.components.create.buttons.create') : $t('admin.accounts.components.edit.buttons.save') }}
            </v-btn>

            <v-btn
              variant="outlined"
              @click="handleCancel"
            >
              {{ $t('admin.accounts.components.create.buttons.cancel') }}
            </v-btn>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRSDStore } from '../../../infrastructure/stores/rsdStore'
import { accountService } from '../services/accountService'
import type { Account } from '../types'

const { t } = useI18n()
const rsdStore = useRSDStore()

// Form data
const form = ref({
  email: '',
  password: '',
  system_role: 'member',
})

// Form state
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

// Emits
const emit = defineEmits<{
  success: [message: string]
  error: [message: string]
}>()

// Computed
const systemRoles = computed(() => [
  { value: 'admin', text: t('admin.accounts.system_roles.admin') },
  { value: 'tenant_admin', text: t('admin.accounts.system_roles.tenant_admin') },
  { value: 'member', text: t('admin.accounts.system_roles.member') }
])

// Watch for data changes (when opening edit mode)
watch(() => rsdStore.data, (newData) => {
  if (newData && (rsdStore.isEditMode || rsdStore.isViewMode)) {
    form.value = {
      email: newData.email || '',
      password: '',
      system_role: newData.system_role || 'member',
    }
  }
}, { immediate: true })

// Reset form when switching to create mode
watch(() => rsdStore.mode, (newMode) => {
  if (newMode === 'create') {
    form.value = {
      email: '',
      password: '',
      system_role: 'member',
    }
    errors.value = {}
  }
})

// Methods
const formatDateTime = (dateString?: string) => {
  if (!dateString) return '-'
  
  const date = new Date(dateString)
  return date.toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getSystemRoleLabel = (role?: string) => {
  if (!role) return t('admin.accounts.system_roles.no_role')
  const roleKey = role.replace(/_/g, '_')
  return t(`admin.accounts.system_roles.${roleKey}`, role)
}

const getSystemRoleColor = (role?: string) => {
  if (!role) return 'grey'
  const colorMap: Record<string, string> = {
    'admin': 'error',
    'tenant_admin': 'warning',
    'member': 'info'
  }
  return colorMap[role] || 'grey'
}

const handleSave = async () => {
  loading.value = true
  errors.value = {}
  
  try {
    let result: Account
    
    if (rsdStore.isCreateMode) {
      result = await accountService.createAccount(form.value)
      emit('success', t('admin.accounts.messages.create_success'))
    } else {
      result = await accountService.updateAccount(rsdStore.data.id, {
        email: form.value.email,
        system_role: form.value.system_role
      })
      emit('success', t('admin.accounts.messages.update_success'))
    }

    // Update RSD data with result
    rsdStore.setData(result)
    rsdStore.handleSuccess()

  } catch (error: any) {
    console.error('Error saving account:', error)

    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    }

    const message = error.response?.data?.message || t(rsdStore.isCreateMode ? 'admin.accounts.messages.create_error' : 'admin.accounts.messages.update_error')
    emit('error', message)
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  if (rsdStore.isCreateMode) {
    rsdStore.close()
  } else {
    // Switch back to view mode
    rsdStore.open('account', 'view', rsdStore.data)
  }
}
</script>

<style scoped>
.account-rsd {
  height: 100%;
}

.text-caption {
  font-size: 0.75rem;
  opacity: 0.7;
}

.text-body-1 {
  font-size: 0.875rem;
  line-height: 1.5;
}

.d-flex.gap-2 {
  gap: 8px;
}
</style>