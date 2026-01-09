<template>
  <div class="account-edit">
    <v-form ref="form" @submit.prevent="handleSubmit">
      <v-card>
        <v-card-text>
          <v-row>
            <!-- Basic Information -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4">
                <v-icon class="mr-2">mdi-information</v-icon>
                {{ $t('admin.accounts.components.edit.general_info') }}
              </h3>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.username"
                :label="$t('admin.accounts.components.edit.fields.username')"
                variant="outlined"
                :disabled="loading"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.email"
                :label="$t('admin.accounts.components.edit.fields.email')"
                type="email"
                variant="outlined"
                :disabled="loading"
              />
            </v-col>

            <!-- Account Settings -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4 mt-4">
                <v-icon class="mr-2">mdi-cog</v-icon>
                {{ $t('admin.accounts.components.edit.settings') }}
              </h3>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="formData.system_role"
                :label="$t('admin.accounts.components.edit.fields.system_role')"
                :items="systemRoles"
                variant="outlined"
                :disabled="loading"
                item-title="text"
                item-value="value"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="formData.is_active"
                :label="$t('admin.accounts.components.edit.fields.is_active')"
                :disabled="loading"
                color="success"
                hide-details
              />
              <p class="text-caption text-medium-emphasis mt-1">
                {{ $t('admin.accounts.components.edit.fields.is_active_hint') }}
              </p>
            </v-col>

            <!-- Password Change -->
            <v-col cols="12">
              <v-expansion-panels v-model="passwordPanel">
                <v-expansion-panel>
                  <v-expansion-panel-title>
                    <v-icon class="mr-2">mdi-lock</v-icon>
                    {{ $t('admin.accounts.components.edit.password_change') }}
                  </v-expansion-panel-title>
                  <v-expansion-panel-text>
                    <v-row>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="passwordData.password"
                          :label="$t('admin.accounts.components.edit.fields.new_password')"
                          type="password"
                          variant="outlined"
                          :disabled="loading"
                        />
                      </v-col>

                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="passwordData.password_confirmation"
                          :label="$t('admin.accounts.components.edit.fields.password_confirmation')"
                          type="password"
                          variant="outlined"
                          :disabled="loading"
                        />
                      </v-col>
                    </v-row>
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>
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
            {{ $t('admin.accounts.components.edit.buttons.cancel') }}
          </v-btn>
          <v-btn
            type="submit"
            color="primary"
            :loading="loading"
            :disabled="false"
          >
            <v-icon class="mr-1">mdi-content-save</v-icon>
            {{ $t('admin.accounts.components.edit.buttons.save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Account, UpdateAccountRequest } from '../types'
import { accountService } from '../services/accountService'

const { t } = useI18n()

interface Props {
  data: Account | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  success: [message: string]
  error: [message: string]
  close: []
}>()

// Form state
const form = ref()
const loading = ref(false)
const error = ref<string | null>(null)
const passwordPanel = ref()

// Form data
const formData = ref<UpdateAccountRequest>({
  username: '',
  email: '',
  system_role: null,
  is_active: true
})

const passwordData = ref({
  password: '',
  password_confirmation: ''
})

// Computed
const changePassword = computed(() => {
  return passwordPanel.value === 0 && (passwordData.value.password || passwordData.value.password_confirmation)
})

const systemRoles = computed(() => [
  { value: null, text: t('admin.accounts.system_roles.no_role') },
  { value: 'admin', text: t('admin.accounts.system_roles.admin') },
  { value: 'tenant_admin', text: t('admin.accounts.system_roles.tenant_admin') },
  { value: 'member', text: t('admin.accounts.system_roles.member') }
])


// Methods
const initializeForm = () => {
  if (props.data) {
    formData.value = {
      username: props.data.username,
      email: props.data.email,
      system_role: props.data.system_role,
      is_active: props.data.is_active
    }
  }
}

const handleSubmit = async () => {
  if (!props.data) return

  loading.value = true
  error.value = null

  try {
    const updateData: UpdateAccountRequest = { ...formData.value }
    
    // Add password if changed
    if (changePassword.value && passwordData.value.password) {
      updateData.password = passwordData.value.password
      updateData.password_confirmation = passwordData.value.password_confirmation
    }

    const response = await accountService.updateAccount(props.data.id, updateData)
    emit('success', response.message || t('admin.accounts.messages.update_success'))
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || t('admin.accounts.messages.update_error')
    emit('error', error.value)
  } finally {
    loading.value = false
  }
}

// Initialize form data when component mounts
onMounted(() => {
  initializeForm()
})
</script>

<style scoped>
.account-edit {
  max-width: 100%;
}

.v-text-field,
.v-select {
  margin-bottom: 8px;
}

.v-expansion-panel-text :deep(.v-expansion-panel-text__wrapper) {
  padding-top: 16px;
}
</style>