<template>
  <div class="tenant-edit">
    <v-form ref="form" @submit.prevent="handleSubmit">
      <v-card>
        <v-card-text>
          <v-row>
            <!-- Basic Information -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4">
                <v-icon class="mr-2">mdi-information</v-icon>
                {{ $t('admin.tenants.components.create.basic_info') }}
              </h3>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.name"
                :label="$t('admin.tenants.components.create.fields.name')"
                variant="outlined"
                :disabled="loading"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.slug"
                :label="$t('admin.tenants.components.create.fields.slug')"
                variant="outlined"
                :disabled="loading"
                :hint="$t('admin.tenants.components.create.fields.slug_hint_edit')"
                persistent-hint
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="formData.description"
                :label="$t('admin.tenants.detail.form.description')"
                variant="outlined"
                :disabled="loading"
                rows="3"
                counter="500"
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
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="formData.is_active"
                :label="$t('admin.tenants.detail.form.is_active')"
                :disabled="loading"
                color="success"
                hide-details
              />
              <p class="text-caption text-medium-emphasis mt-1">
                {{ $t('admin.tenants.components.create.fields.inactive_hint') }}
              </p>
            </v-col>

            <!-- Advanced Settings -->
            <v-col cols="12">
              <v-expansion-panels v-model="settingsPanel">
                <v-expansion-panel>
                  <v-expansion-panel-title>
                    <v-icon class="mr-2">mdi-tune</v-icon>
                    {{ $t('admin.tenants.components.create.fields.advanced_settings') }}
                  </v-expansion-panel-title>
                  <v-expansion-panel-text>
                    <v-row>
                      <v-col cols="12">
                        <v-textarea
                          v-model="settingsJson"
                          :label="$t('admin.tenants.components.create.fields.settings_json')"
                          variant="outlined"
                          :disabled="loading"
                          rows="6"
                          placeholder="{}"
                          :hint="$t('admin.tenants.components.create.fields.settings_json_hint')"
                          persistent-hint
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
            {{ $t('admin.tenants.components.create.buttons.cancel') }}
          </v-btn>
          <v-btn
            type="submit"
            color="primary"
            :loading="loading"
            :disabled="false"
          >
            <v-icon class="mr-1">mdi-content-save</v-icon>
            {{ $t('admin.tenants.components.create.buttons.save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Tenant, UpdateTenantRequest } from '../types'
import { tenantService } from '../services/tenantService'

const { t } = useI18n()

interface Props {
  data: Tenant | null
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
const settingsPanel = ref()

// Form data
const formData = ref<UpdateTenantRequest>({
  name: '',
  slug: '',
  description: '',
  is_personal: false,
  is_active: true,
  settings: {}
})

// Settings as JSON string for easier editing
const settingsJson = ref('{}')

// Computed
const tenantTypes = computed(() => [
  { value: false, text: t('admin.tenants.detail.tenant_types.company') },
  { value: true, text: t('admin.tenants.detail.tenant_types.personal') }
])


// Methods
const initializeForm = () => {
  if (props.data) {
    formData.value = {
      name: props.data.name,
      slug: props.data.slug,
      description: props.data.description || '',
      is_personal: props.data.is_personal,
      is_active: props.data.is_active,
      settings: props.data.settings || {}
    }
    settingsJson.value = JSON.stringify(props.data.settings || {}, null, 2)
  }
}

const handleSubmit = async () => {
  if (!props.data) return

  loading.value = true
  error.value = null

  try {
    // Parse settings JSON
    let settings = {}
    try {
      settings = JSON.parse(settingsJson.value)
    } catch {
      throw new Error(t('admin.tenants.messages.json_error'))
    }

    const updateData: UpdateTenantRequest = {
      ...formData.value,
      settings
    }

    const response = await tenantService.updateTenant(props.data.id, updateData)
    emit('success', response.message)
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || t('admin.tenants.messages.update_error')
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
.tenant-edit {
  max-width: 100%;
}

.v-text-field,
.v-textarea,
.v-select {
  margin-bottom: 8px;
}

.v-expansion-panel-text :deep(.v-expansion-panel-text__wrapper) {
  padding-top: 16px;
}
</style>