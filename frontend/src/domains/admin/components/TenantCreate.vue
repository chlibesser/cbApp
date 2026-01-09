<template>
  <div class="tenant-create">
    <v-form ref="form" @submit.prevent="handleSubmit">
      <v-card>
        <v-card-text>
          <v-row>
            <!-- Basic Information -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4">
                <v-icon class="mr-2">mdi-information</v-icon>
                {{ $t('admin.tenants.components.create.title') }}
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-4">
                {{ $t('admin.tenants.components.create.description') }}
              </p>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.name"
                :label="$t('admin.tenants.components.create.fields.name')"
                variant="outlined"
                :disabled="loading"
                @input="generateSlug"
                :hint="$t('admin.tenants.components.create.fields.name_hint')"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.slug"
                :label="$t('admin.tenants.components.create.fields.slug')"
                variant="outlined"
                :disabled="loading"
                :hint="$t('admin.tenants.components.create.fields.slug_hint')"
                persistent-hint
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="formData.description"
                :label="$t('admin.tenants.components.create.fields.description')"
                variant="outlined"
                :disabled="loading"
                rows="3"
                counter="500"
                :hint="$t('admin.tenants.components.create.fields.description_hint')"
                persistent-hint
              />
            </v-col>

            <!-- Tenant Settings -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4 mt-4">
                <v-icon class="mr-2">mdi-cog</v-icon>
                {{ $t('admin.tenants.components.create.settings') }}
              </h3>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="formData.is_personal"
                :label="$t('admin.tenants.components.create.fields.type')"
                :items="tenantTypes"
                variant="outlined"
                :disabled="loading"
                item-title="text"
                item-value="value"
                :hint="$t('admin.tenants.components.create.fields.type_hint')"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="formData.is_active"
                :label="$t('admin.tenants.components.create.fields.activate')"
                :disabled="loading"
                color="success"
                hide-details
              />
              <p class="text-caption text-medium-emphasis mt-1">
                {{ $t('admin.tenants.components.create.fields.activate_hint') }}
              </p>
            </v-col>

            <!-- Preview Card -->
            <v-col cols="12">
              <v-card variant="outlined" class="mt-4">
                <v-card-title class="text-subtitle-1 bg-grey-lighten-5">
                  <v-icon class="mr-2">mdi-eye</v-icon>
                  {{ $t('admin.tenants.components.create.preview') }}
                </v-card-title>
                <v-card-text>
                  <v-list lines="one" density="compact">
                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.tenants.components.create.preview_labels.name') }}</v-list-item-title>
                      <template #append>
                        <span class="font-weight-medium">{{ formData.name || $t('admin.tenants.components.create.preview_labels.no_name') }}</span>
                      </template>
                    </v-list-item>

                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.tenants.components.create.preview_labels.slug') }}</v-list-item-title>
                      <template #append>
                        <span class="font-weight-medium">{{ formData.slug || $t('admin.tenants.components.create.preview_labels.auto_generated') }}</span>
                      </template>
                    </v-list-item>

                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.tenants.components.create.preview_labels.type') }}</v-list-item-title>
                      <template #append>
                        <v-chip size="small" :color="formData.is_personal ? 'info' : 'primary'">
                          {{ formData.is_personal ? $t('admin.tenants.detail.tenant_types.personal') : $t('admin.tenants.detail.tenant_types.company') }}
                        </v-chip>
                      </template>
                    </v-list-item>

                    <v-list-item>
                      <v-list-item-title>{{ $t('admin.tenants.components.create.preview_labels.status') }}</v-list-item-title>
                      <template #append>
                        <v-chip size="small" :color="formData.is_active ? 'success' : 'warning'">
                          {{ formData.is_active ? $t('admin.tenants.status.active') : $t('admin.tenants.status.inactive') }}
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
            {{ $t('admin.tenants.components.create.buttons.cancel') }}
          </v-btn>
          <v-btn
            type="submit"
            color="primary"
            :loading="loading"
            :disabled="false"
          >
            <v-icon class="mr-1">mdi-plus</v-icon>
            {{ $t('admin.tenants.components.create.buttons.create') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { CreateTenantRequest } from '../types'
import { tenantService } from '../services/tenantService'

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
const formData = ref<CreateTenantRequest>({
  name: '',
  slug: '',
  description: '',
  is_personal: false,
  is_active: true,
  settings: {}
})

// Computed
const tenantTypes = computed(() => [
  {
    value: false,
    text: t('admin.tenants.detail.tenant_types.company'),
    description: t('admin.tenants.types.company')
  },
  {
    value: true,
    text: t('admin.tenants.detail.tenant_types.personal'),
    description: t('admin.tenants.types.personal')
  }
])


// Methods
const generateSlug = () => {
  if (formData.value.name && !formData.value.slug) {
    // Auto-generate slug from name
    let slug = formData.value.name
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
      .replace(/\s+/g, '-') // Replace spaces with hyphens
      .replace(/-+/g, '-') // Replace multiple hyphens with single
      .trim()
    
    // Remove leading/trailing hyphens
    slug = slug.replace(/^-+|-+$/g, '')
    
    formData.value.slug = slug
  }
}

const handleSubmit = async () => {

  loading.value = true
  error.value = null

  try {
    const response = await tenantService.createTenant(formData.value)
    emit('success', response.message)
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || t('admin.tenants.messages.create_error')
    emit('error', error.value)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.tenant-create {
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