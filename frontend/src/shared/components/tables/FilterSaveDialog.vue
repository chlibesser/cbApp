<template>
  <v-dialog
    v-model="dialog"
    max-width="450"
    persistent
  >
    <v-card>
      <v-card-title class="text-h6">
        {{ isEditing ? 'Filter bearbeiten' : 'Filter speichern' }}
      </v-card-title>

      <v-card-text>
        <v-form ref="formRef" v-model="formValid">
          <v-text-field
            v-model="form.name"
            label="Filter-Name"
            :rules="[rules.required, rules.maxLength]"
            counter="100"
            variant="outlined"
            density="comfortable"
            class="mb-4"
          />

          <div class="mb-4">
            <label class="text-body-2 text-medium-emphasis mb-2 d-block">
              Button-Farbe
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
            label="Als Quick-Access Button anzeigen"
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
          Abbrechen
        </v-btn>
        <v-btn
          color="primary"
          variant="flat"
          :disabled="!formValid"
          :loading="loading"
          @click="handleSave"
        >
          {{ isEditing ? 'Speichern' : 'Erstellen' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { FILTER_COLOR_PRESETS } from '@/types/tableFilter'
import type { TableFilter, TableFilterState } from '@/types/tableFilter'

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
const formValid = ref(false)
const loading = ref(false)

const form = ref({
  name: '',
  color: FILTER_COLOR_PRESETS[0],
  showAsButton: false,
})

const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const isEditing = computed(() => !!props.existingFilter)

const colorPresets = FILTER_COLOR_PRESETS

const rules = {
  required: (v: string) => !!v?.trim() || 'Dieses Feld ist erforderlich',
  maxLength: (v: string) =>
    !v || v.length <= 100 || 'Maximal 100 Zeichen erlaubt',
}

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
          showAsButton: false,
        }
      }
    }
  }
)

const handleCancel = () => {
  dialog.value = false
}

const handleSave = async () => {
  const { valid } = await formRef.value?.validate()
  if (!valid) return

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
