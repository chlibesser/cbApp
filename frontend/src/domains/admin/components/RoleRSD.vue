<template>
  <div class="role-rsd">
    <!-- View Mode -->
    <v-container v-if="rsdStore.isViewMode" class="pa-4 pl-2 pr-4">
      <v-row>
        <v-col cols="12">
          <h3 class="text-h6 mb-4">{{ $t('admin.roles.components.rsd.view.title') }}</h3>
        </v-col>

        <v-col cols="12">
          <div class="text-caption text-grey mb-1">{{ $t('admin.roles.components.rsd.view.name') }}</div>
          <div class="text-body-1">{{ rsdStore.data?.name || '-' }}</div>
        </v-col>

        <v-col cols="12">
          <div class="text-caption text-grey mb-1">{{ $t('admin.roles.components.rsd.view.description') }}</div>
          <div class="text-body-1">{{ rsdStore.data?.description || '-' }}</div>
        </v-col>

        <v-col cols="12">
          <div class="text-caption text-grey mb-1">{{ $t('admin.roles.components.rsd.view.tenant') }}</div>
          <div class="text-body-1">{{ rsdStore.data?.tenant?.name || '-' }}</div>
        </v-col>

        <v-col cols="12">
          <div class="text-caption text-grey mb-1">{{ $t('admin.roles.components.rsd.view.created') }}</div>
          <div class="text-body-1">{{ formatDateTime(rsdStore.data?.created_at) }}</div>
        </v-col>

        <v-col cols="12">
          <div class="text-caption text-grey mb-1">{{ $t('admin.roles.components.rsd.view.updated') }}</div>
          <div class="text-body-1">{{ formatDateTime(rsdStore.data?.updated_at) }}</div>
        </v-col>
      </v-row>
    </v-container>

    <!-- Edit/Create Mode -->
    <v-container v-else class="pa-4 pl-2 pr-4">
      <v-row>
        <v-col cols="12">
          <h3 class="text-h6 mb-4">
            {{ rsdStore.isCreateMode ? $t('admin.roles.components.rsd.create.title') : $t('admin.roles.components.rsd.edit.title') }}
          </h3>
        </v-col>

        <v-col cols="12">
          <v-text-field
            v-model="form.name"
            :label="$t('admin.roles.components.rsd.fields.name') + ' *'"
            variant="outlined"
            density="compact"
            :error-messages="errors.name"
          />
        </v-col>

        <v-col cols="12">
          <v-textarea
            v-model="form.description"
            :label="$t('admin.roles.components.rsd.fields.description')"
            variant="outlined"
            density="compact"
            rows="3"
            :error-messages="errors.description"
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
              {{ rsdStore.isCreateMode ? $t('admin.roles.components.rsd.buttons.create') : $t('admin.roles.components.rsd.buttons.save') }}
            </v-btn>

            <v-btn
              variant="outlined"
              @click="handleCancel"
            >
              {{ $t('admin.roles.components.rsd.buttons.cancel') }}
            </v-btn>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRSDStore } from '../../../infrastructure/stores/rsdStore'

const { t } = useI18n()
const rsdStore = useRSDStore()

// Form data
const form = ref({
  name: '',
  description: '',
})

// Form state
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

// Emits
const emit = defineEmits<{
  success: [message: string]
  error: [message: string]
}>()

// Watch for data changes
watch(() => rsdStore.data, (newData) => {
  if (newData && (rsdStore.isEditMode || rsdStore.isViewMode)) {
    form.value = {
      name: newData.name || '',
      description: newData.description || '',
    }
  }
}, { immediate: true })

// Reset form when switching to create mode
watch(() => rsdStore.mode, (newMode) => {
  if (newMode === 'create') {
    form.value = {
      name: '',
      description: '',
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

const handleSave = async () => {
  loading.value = true
  errors.value = {}

  try {
    // TODO: Implement role service calls
    emit('success', t('admin.roles.components.rsd.messages.not_implemented'))

  } catch (error: any) {
    console.error('Error saving role:', error)
    emit('error', t('admin.roles.components.rsd.messages.save_error'))
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  if (rsdStore.isCreateMode) {
    rsdStore.close()
  } else {
    rsdStore.open('role', 'view', rsdStore.data)
  }
}
</script>

<style scoped>
.role-rsd {
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