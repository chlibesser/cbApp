<template>
  <v-menu
    v-model="menuOpen"
    :close-on-content-click="false"
    location="bottom"
  >
    <template #activator="{ props: menuProps }">
      <v-icon
        v-bind="menuProps"
        size="small"
        :color="hasFilter ? 'primary' : undefined"
        class="column-filter-icon"
        @click.stop
      >
        mdi-filter
      </v-icon>
    </template>

    <v-card min-width="280" class="column-filter-card">
      <v-card-title class="text-subtitle-1 pb-2">
        {{ column.title }} filtern
      </v-card-title>

      <v-card-text class="pt-0">
        <!-- Operator Auswahl -->
        <v-select
          v-model="localOperator"
          :items="operatorOptions"
          item-title="label"
          item-value="value"
          label="Bedingung"
          variant="outlined"
          density="compact"
          hide-details
          class="mb-3"
        />

        <!-- Wert-Eingabe basierend auf Typ und Operator -->
        <template v-if="!isEmptyOperator">
          <!-- Text Filter -->
          <v-text-field
            v-if="isTextFilter"
            v-model="localValue"
            label="Wert"
            variant="outlined"
            density="compact"
            hide-details
            clearable
          />

          <!-- Number Filter -->
          <template v-if="isNumberFilter">
            <v-text-field
              v-if="!isBetweenOperator"
              v-model.number="localValue"
              label="Wert"
              type="number"
              variant="outlined"
              density="compact"
              hide-details
              clearable
            />
            <div v-else class="d-flex gap-2">
              <v-text-field
                v-model.number="localValueMin"
                label="Von"
                type="number"
                variant="outlined"
                density="compact"
                hide-details
              />
              <v-text-field
                v-model.number="localValueMax"
                label="Bis"
                type="number"
                variant="outlined"
                density="compact"
                hide-details
              />
            </div>
          </template>

          <!-- Boolean Filter -->
          <v-select
            v-if="isBooleanFilter"
            v-model="localValue"
            :items="booleanOptions"
            item-title="text"
            item-value="value"
            label="Wert"
            variant="outlined"
            density="compact"
            hide-details
          />

          <!-- Select Filter (Multiselect) -->
          <v-select
            v-if="isSelectFilter"
            v-model="localValueArray"
            :items="column.filterOptions || []"
            item-title="text"
            item-value="value"
            label="Werte auswählen"
            variant="outlined"
            density="compact"
            hide-details
            multiple
            chips
            closable-chips
          />

          <!-- Date Filter -->
          <template v-if="isDateFilter">
            <v-text-field
              v-if="!isBetweenOperator"
              v-model="localValue"
              label="Datum"
              type="date"
              variant="outlined"
              density="compact"
              hide-details
            />
            <div v-else class="d-flex gap-2">
              <v-text-field
                v-model="localValueMin"
                label="Von"
                type="date"
                variant="outlined"
                density="compact"
                hide-details
              />
              <v-text-field
                v-model="localValueMax"
                label="Bis"
                type="date"
                variant="outlined"
                density="compact"
                hide-details
              />
            </div>
          </template>
        </template>
      </v-card-text>

      <v-card-actions class="pt-0">
        <v-btn
          variant="text"
          size="small"
          :disabled="!hasFilter"
          @click="clearFilter"
        >
          Löschen
        </v-btn>
        <v-spacer />
        <v-btn
          color="primary"
          variant="flat"
          size="small"
          :disabled="!canApply"
          @click="applyFilter"
        >
          Anwenden
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-menu>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { TableColumn } from '@/types/table'
import type { ColumnFilter, FilterOperator, FilterOperatorOption } from '@/types/tableFilter'
import {
  TEXT_OPERATORS,
  NUMBER_OPERATORS,
  DATE_OPERATORS,
  BOOLEAN_OPERATORS,
  SELECT_OPERATORS
} from '@/types/tableFilter'

interface Props {
  column: TableColumn
  modelValue: ColumnFilter | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [filter: ColumnFilter | null]
}>()

// Local state
const menuOpen = ref(false)
const localOperator = ref<FilterOperator>('contains')
const localValue = ref<string | number | boolean | null>(null)
const localValueMin = ref<string | number | null>(null)
const localValueMax = ref<string | number | null>(null)
const localValueArray = ref<string[]>([])

// Boolean options
const booleanOptions = [
  { value: true, text: 'Ja' },
  { value: false, text: 'Nein' }
]

// Determine filter type based on column type
const filterType = computed(() => {
  // Use explicit filterType if set
  if (props.column.filterType) {
    return props.column.filterType
  }

  // Map column type to filter type
  const columnType = props.column.type || 'text'
  switch (columnType) {
    case 'text':
    case 'email':
    case 'phone':
    case 'url':
      return 'text'
    case 'number':
    case 'currency':
    case 'percentage':
      return 'number'
    case 'boolean':
      return 'boolean'
    case 'select':
      return 'select'
    case 'date':
    case 'datetime':
      return 'date'
    default:
      return 'text'
  }
})

// Computed flags for filter types
const isTextFilter = computed(() => filterType.value === 'text')
const isNumberFilter = computed(() => filterType.value === 'number')
const isBooleanFilter = computed(() => filterType.value === 'boolean')
const isSelectFilter = computed(() => filterType.value === 'select')
const isDateFilter = computed(() => filterType.value === 'date' || filterType.value === 'datetime')

// Operator checks
const isEmptyOperator = computed(() =>
  localOperator.value === 'isEmpty' || localOperator.value === 'isNotEmpty'
)
const isBetweenOperator = computed(() => localOperator.value === 'between')

// Get available operators based on filter type
const operatorOptions = computed((): FilterOperatorOption[] => {
  switch (filterType.value) {
    case 'text':
      return TEXT_OPERATORS
    case 'number':
      return NUMBER_OPERATORS
    case 'boolean':
      return BOOLEAN_OPERATORS
    case 'select':
      return SELECT_OPERATORS
    case 'date':
    case 'datetime':
      return DATE_OPERATORS
    default:
      return TEXT_OPERATORS
  }
})

// Check if filter is active
const hasFilter = computed(() => props.modelValue !== null)

// Check if can apply filter
const canApply = computed(() => {
  if (isEmptyOperator.value) {
    return true
  }

  if (isBetweenOperator.value) {
    return localValueMin.value !== null && localValueMax.value !== null
  }

  if (isSelectFilter.value) {
    return localValueArray.value.length > 0
  }

  return localValue.value !== null && localValue.value !== ''
})

// Initialize local state from modelValue
const initializeFromModel = () => {
  if (props.modelValue) {
    localOperator.value = props.modelValue.operator

    if (isBetweenOperator.value && Array.isArray(props.modelValue.value)) {
      localValueMin.value = props.modelValue.value[0]
      localValueMax.value = props.modelValue.value[1]
    } else if (isSelectFilter.value && Array.isArray(props.modelValue.value)) {
      localValueArray.value = props.modelValue.value as string[]
    } else {
      localValue.value = props.modelValue.value as string | number | boolean | null
    }
  } else {
    // Set default operator based on filter type
    localOperator.value = operatorOptions.value[0]?.value || 'contains'
    localValue.value = null
    localValueMin.value = null
    localValueMax.value = null
    localValueArray.value = []
  }
}

// Watch modelValue changes
watch(() => props.modelValue, initializeFromModel, { immediate: true })

// Reset values when operator changes
watch(localOperator, (newOp, oldOp) => {
  if (newOp !== oldOp) {
    // Don't reset if we're just initializing
    if (oldOp !== undefined) {
      localValue.value = null
      localValueMin.value = null
      localValueMax.value = null
      localValueArray.value = []
    }
  }
})

// Apply filter
const applyFilter = () => {
  let value: ColumnFilter['value']

  if (isEmptyOperator.value) {
    value = null
  } else if (isBetweenOperator.value) {
    value = [localValueMin.value!, localValueMax.value!]
  } else if (isSelectFilter.value) {
    value = localValueArray.value
  } else {
    value = localValue.value
  }

  const filter: ColumnFilter = {
    columnKey: props.column.key,
    operator: localOperator.value,
    value
  }

  emit('update:modelValue', filter)
  menuOpen.value = false
}

// Clear filter
const clearFilter = () => {
  localOperator.value = operatorOptions.value[0]?.value || 'contains'
  localValue.value = null
  localValueMin.value = null
  localValueMax.value = null
  localValueArray.value = []
  emit('update:modelValue', null)
  menuOpen.value = false
}
</script>

<style scoped>
.column-filter-icon {
  cursor: pointer;
  opacity: 0.6;
  transition: opacity 0.2s;
}

.column-filter-icon:hover {
  opacity: 1;
}

.column-filter-card {
  max-height: 400px;
  overflow-y: auto;
}
</style>
