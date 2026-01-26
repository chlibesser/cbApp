<template>
  <v-dialog
    v-model="dialog"
    max-width="450"
    persistent
  >
    <v-card>
      <v-card-title class="text-h6">
        {{ isEditing ? t('filter.dialog.edit_title') : t('filter.dialog.save_title') }}
      </v-card-title>

      <v-card-text>
        <v-form ref="formRef">
          <v-text-field
            v-model="form.name"
            :label="t('filter.dialog.name_label')"
            counter="100"
            variant="outlined"
            density="comfortable"
            class="mb-4"
          />

          <div class="mb-4">
            <label class="text-body-2 text-medium-emphasis mb-2 d-block">
              {{ t('filter.dialog.color_label') }}
            </label>
            <div class="d-flex flex-wrap ga-2">
              <v-btn
                v-for="color in colorPresets"
                :key="color"
                :color="color"
                :variant="form.color === color ? 'flat' : 'outlined'"
                size="small"
                icon
                @click="form.color = color"
              >
                <v-icon v-if="form.color === color">mdi-check</v-icon>
              </v-btn>
            </div>
          </div>

          <v-checkbox
            v-model="form.showAsButton"
            :label="t('filter.dialog.show_as_button')"
            density="comfortable"
            hide-details
          />
        </v-form>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn
          variant="text"
          @click="handleCancel"
        >
          {{ t('buttons.cancel') }}
        </v-btn>
        <v-btn
          color="primary"
          variant="flat"
          :loading="loading"
          @click="handleSave"
        >
          {{ isEditing ? t('buttons.save') : t('buttons.create') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useTranslations } from '@/core/localization/composables/useTranslations'
import { FILTER_COLOR_PRESETS } from '@/types/tableFilter'
import type { TableFilter, TableFilterState } from '@/types/tableFilter'

const { t } = useTranslations('admin.common')

interface Props {
  modelValue: boolean
  filterState: TableFilterState
  tableKey: string
  existingFilter?: TableFilter | null
}

const props = withDefaults(defineProps<Props>(), {
  existingFilter: null,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  save: [data: { name: string; color: string; showAsButton: boolean }]
}>()

const formRef = ref()
const loading = ref(false)

const form = ref({
  name: '',
  color: FILTER_COLOR_PRESETS[0],
  showAsButton: true,
})

const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const isEditing = computed(() => !!props.existingFilter)

const colorPresets = FILTER_COLOR_PRESETS

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      if (props.existingFilter) {
        form.value = {
          name: props.existingFilter.name,
          color: props.existingFilter.color,
          showAsButton: props.existingFilter.show_as_button,
        }
      } else {
        form.value = {
          name: '',
          color: FILTER_COLOR_PRESETS[0],
          showAsButton: true,
        }
      }
    }
  }
)

const handleCancel = () => {
  dialog.value = false
}

const handleSave = async () => {
  loading.value = true
  try {
    emit('save', {
      name: form.value.name.trim(),
      color: form.value.color,
      showAsButton: form.value.showAsButton,
    })
    dialog.value = false
  } finally {
    loading.value = false
  }
}
</script>
