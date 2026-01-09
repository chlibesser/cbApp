<template>
  <div class="account-view">
    <v-card>
      <v-card-text>
        <div v-if="loading" class="text-center py-8">
          <v-progress-circular indeterminate />
          <p class="mt-2">{{ $t('admin.accounts.components.view.loading') }}</p>
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
                  {{ $t('admin.accounts.components.view.general_info') }}
                </v-card-title>
                <v-card-text>
                  <v-list lines="two">
                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.accounts.fields.username') }}</v-list-item-title>
                      <v-list-item-subtitle>{{ account.username }}</v-list-item-subtitle>
                    </v-list-item>

                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.accounts.fields.email') }}</v-list-item-title>
                      <v-list-item-subtitle>{{ account.email }}</v-list-item-subtitle>
                    </v-list-item>

                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.accounts.fields.system_role') }}</v-list-item-title>
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
                      <v-list-item-title>{{ $t('admin.accounts.fields.status') }}</v-list-item-title>
                      <v-list-item-subtitle>
                        <v-chip
                          :color="account.is_active ? 'success' : 'error'"
                          size="small"
                          variant="flat"
                        >
                          <v-icon size="small" class="mr-1">
                            {{ account.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}
                          </v-icon>
                          {{ account.is_active ? $t('admin.accounts.status.active') : $t('admin.accounts.status.locked') }}
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
                  {{ $t('admin.accounts.components.view.timestamps') }}
                </v-card-title>
                <v-card-text>
                  <v-list lines="two">
                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.accounts.components.view.created_label') }}</v-list-item-title>
                      <v-list-item-subtitle>{{ formatDate(account.created_at) }}</v-list-item-subtitle>
                    </v-list-item>

                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.accounts.components.view.updated_label') }}</v-list-item-title>
                      <v-list-item-subtitle>{{ formatDate(account.updated_at) }}</v-list-item-subtitle>
                    </v-list-item>

                    <v-list-item v-if="account.email_verified_at">
                      <v-list-item-title>{{ $t('admin.accounts.components.view.email_verified_label') }}</v-list-item-title>
                      <v-list-item-subtitle>{{ formatDate(account.email_verified_at) }}</v-list-item-subtitle>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Associated Tenants Section -->
          <div class="mt-6">
            <h3 class="text-h6 mb-4">{{ $t('admin.accounts.components.view.assigned_tenants') }}</h3>

            <v-card variant="outlined">
              <v-card-text>
                <div v-if="!account.tenants || account.tenants.length === 0" class="text-center py-8">
                  <v-icon size="64" color="grey-lighten-1">mdi-office-building-off</v-icon>
                  <p class="text-h6 mt-2">{{ $t('admin.accounts.components.view.no_tenants_title') }}</p>
                  <p class="text-body-2 text-medium-emphasis">
                    {{ $t('admin.accounts.components.view.no_tenants_description') }}
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
                        {{ tenant.is_personal ? $t('admin.tenants.detail.tenant_types.personal') : $t('admin.tenants.detail.tenant_types.company') }}
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
          <p class="text-h6 mt-2">{{ $t('admin.accounts.messages.not_found') }}</p>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Account } from '../types'
import { accountService } from '../services/accountService'

const { t } = useI18n()

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