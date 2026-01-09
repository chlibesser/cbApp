<template>
  <div class="account-create">
    <v-form ref="form" @submit.prevent="handleSubmit">
      <v-card>
        <v-card-text>
          <v-row>
            <!-- Basic Information -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4">
                <v-icon class="mr-2">mdi-information</v-icon>
                {{ $t('admin.accounts.components.create.title') }}
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-4">
                {{ $t('admin.accounts.components.create.description') }}
              </p>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.username"
                :label="$t('admin.accounts.components.create.fields.username')"
                variant="outlined"
                :disabled="loading"
                :hint="$t('admin.accounts.components.create.fields.username_hint')"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.email"
                :label="$t('admin.accounts.components.create.fields.email')"
                type="email"
                variant="outlined"
                :disabled="loading"
                :hint="$t('admin.accounts.components.create.fields.email_hint')"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.password"
                :label="$t('admin.accounts.components.create.fields.password')"
                type="password"
                variant="outlined"
                :disabled="loading"
                :hint="$t('admin.accounts.components.create.fields.password_hint')"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.password_confirmation"
                :label="$t('admin.accounts.components.create.fields.password_confirmation')"
                type="password"
                variant="outlined"
                :disabled="loading"
                :hint="$t('admin.accounts.components.create.fields.password_confirmation_hint')"
                persistent-hint
              />
            </v-col>

            <!-- Account Settings -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4 mt-4">
                <v-icon class="mr-2">mdi-cog</v-icon>
                {{ $t('admin.accounts.components.create.settings') }}
              </h3>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="formData.system_role"
                :label="$t('admin.accounts.components.create.fields.system_role')"
                :items="systemRoles"
                variant="outlined"
                :disabled="loading"
                item-title="text"
                item-value="value"
                :hint="$t('admin.accounts.components.create.fields.system_role_hint')"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="formData.is_active"
                :label="$t('admin.accounts.components.create.fields.activate')"
                :disabled="loading"
                color="success"
                hide-details
              />
              <p class="text-caption text-medium-emphasis mt-1">
                {{ $t('admin.accounts.components.create.fields.activate_hint') }}
              </p>
            </v-col>

            <!-- Preview Card -->
            <v-col cols="12">
              <v-card variant="outlined" class="mt-4">
                <v-card-title class="text-subtitle-1 bg-grey-lighten-5">
                  <v-icon class="mr-2">mdi-eye</v-icon>
                  {{ $t('admin.accounts.components.create.preview') }}
                </v-card-title>
                <v-card-text>
                  <v-list lines="one" density="compact">
                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.accounts.components.create.preview_labels.username') }}</v-list-item-title>
                      <template #append>
                        <span class="font-weight-medium">{{ formData.username || $t('admin.accounts.components.create.preview_labels.no_username') }}</span>
                      </template>
                    </v-list-item>

                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.accounts.components.create.preview_labels.email') }}</v-list-item-title>
                      <template #append>
                        <span class="font-weight-medium">{{ formData.email || $t('admin.accounts.components.create.preview_labels.no_email') }}</span>
                      </template>
                    </v-list-item>

                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.accounts.components.create.preview_labels.system_role') }}</v-list-item-title>
                      <template #append>
                        <v-chip size="small" :color="getSystemRoleColor(formData.system_role)">
                          {{ getSystemRoleLabel(formData.system_role) }}
                        </v-chip>
                      </template>
                    </v-list-item>

                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.accounts.components.create.preview_labels.status') }}</v-list-item-title>
                      <template #append>
                        <v-chip size="small" :color="formData.is_active ? 'success' : 'warning'">
                          {{ formData.is_active ? $t('admin.accounts.status.active') : $t('admin.accounts.status.locked') }}
                        </v-chip>
                      </template>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Error Display -->
            <v-col v-if="error" cols="12">
              <v-alert type="error" dismissible @click:close="error = null">
                {{ error }}
              </v-alert>
            </v-col>
          </v-row>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="$emit('close')"
            :disabled="loading"
          >
            {{ $t('admin.accounts.components.create.buttons.cancel') }}
          </v-btn>
          <v-btn
            type="submit"
            color="primary"
            :loading="loading"
            :disabled="false"
          >
            <v-icon class="mr-1">mdi-plus</v-icon>
            {{ $t('admin.accounts.components.create.buttons.create') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { CreateAccountRequest } from '../types'
import { accountService } from '../services/accountService'

const { t } = useI18n()

const emit = defineEmits<{
  success: [message: string]
  error: [message: string] 
  close: []
}>()

// Form state
const form = ref()
const loading = ref(false)
const error = ref<string | null>(null)

// Form data
const formData = ref<CreateAccountRequest>({
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  system_role: 'member',
  is_active: true
})

// Computed
const systemRoles = computed(() => [
  {
    value: 'member',
    text: t('admin.accounts.system_roles.member')
  },
  {
    value: 'tenant_admin',
    text: t('admin.accounts.system_roles.tenant_admin')
  },
  {
    value: 'admin',
    text: t('admin.accounts.system_roles.admin')
  }
])


// Methods
const getSystemRoleLabel = (role: string | null) => {
  if (!role) return t('admin.accounts.system_roles.no_role')
  const roleKey = role.replace(/_/g, '_')
  return t(`admin.accounts.system_roles.${roleKey}`, role)
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

const handleSubmit = async () => {

  loading.value = true
  error.value = null

  try {
    const response = await accountService.createAccount(formData.value)
    emit('success', response.message || t('admin.accounts.messages.create_success'))
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || t('admin.accounts.messages.create_error')
    emit('error', error.value)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.account-create {
  max-width: 100%;
}

.v-text-field,
.v-textarea,
.v-select {
  margin-bottom: 8px;
}

.v-list-item {
  min-height: 40px;
}
</style>