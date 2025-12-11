<template>
  <div class="column-filter">
    <!-- Text Filter -->
    <v-text-field
      v-if="filterType === 'text'"
      v-model="localValue"
      :placeholder="`${column.title} filtern...`"
      density="compact"
      variant="outlined"
      clearable
      hide-details
      @update:modelValue="updateFilter"
    >
      <template #prepend-inner>
        <v-icon size="small">mdi-magnify</v-icon>
      </template>
    </v-text-field>

    <!-- Number Filter -->
    <v-text-field
      v-else-if="filterType === 'number'"
      v-model.number="localValue"
      :placeholder="`${column.title} filtern...`"
      density="compact"
      variant="outlined"
      type="number"
      clearable
      hide-details
      @update:modelValue="updateFilter"
    >
      <template #prepend-inner>
        <v-icon size="small">mdi-numeric</v-icon>
      </template>
    </v-text-field>

    <!-- Select Filter -->
    <v-select
      v-else-if="filterType === 'select'"
      v-model="localValue"
      :items="filterOptions"
      :placeholder="`${column.title} wählen...`"
      density="compact"
      variant="outlined"
      clearable
      hide-details
      @update:modelValue="updateFilter"
    >
      <template #prepend-inner>
        <v-icon size="small">mdi-format-list-bulleted</v-icon>
      </template>
    </v-select>

    <!-- Boolean Filter -->
    <v-select
      v-else-if="filterType === 'boolean'"
      v-model="localValue"
      :items="booleanOptions"
      :placeholder="`${column.title} wählen...`"
      density="compact"
      variant="outlined"
      clearable
      hide-details
      @update:modelValue="updateFilter"
    >
      <template #prepend-inner>
        <v-icon size="small">mdi-toggle-switch</v-icon>
      </template>
    </v-select>

    <!-- Date Filter -->
    <v-text-field
      v-else-if="filterType === 'date'"
      v-model="localValue"
      :placeholder="`${column.title} (DD.MM.YYYY)`"
      density="compact"
      variant="outlined"
      type="date"
      clearable
      hide-details
      @update:modelValue="updateFilter"
    >
      <template #prepend-inner>
        <v-icon size="small">mdi-calendar</v-icon>
      </template>
    </v-text-field>

    <!-- DateTime Filter -->
    <v-text-field
      v-else-if="filterType === 'datetime'"
      v-model="localValue"
      :placeholder="`${column.title} (DD.MM.YYYY HH:mm)`"
      density="compact"
      variant="outlined"
      type="datetime-local"
      clearable
      hide-details
      @update:modelValue="updateFilter"
    >
      <template #prepend-inner>
        <v-icon size="small">mdi-calendar-clock</v-icon>
      </template>
    </v-text-field>

    <!-- Fallback: Text Filter -->
    <v-text-field
      v-else
      v-model="localValue"
      :placeholder="`${column.title} filtern...`"
      density="compact"
      variant="outlined"
      clearable
      hide-details
      @update:modelValue="updateFilter"
    >
      <template #prepend-inner>
        <v-icon size="small">mdi-magnify</v-icon>
      </template>
    </v-text-field>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
// import VueDatePicker from '@vuepic/vue-datepicker'
// import '@vuepic/vue-datepicker/dist/main.css'
import type { TableColumn } from '../../../types/table'

interface Props {
  column: TableColumn
  modelValue?: any
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: any]
}>()

// Local state für bessere Performance
const localValue = ref(props.modelValue)

// Computed properties
const filterType = computed(() => props.column.filterType || 'text')

const filterOptions = computed(() => {
  return props.column.filterOptions || []
})

const booleanOptions = computed(() => [
  { title: 'Ja', value: true },
  { title: 'Nein', value: false }
])

// Watch for prop changes
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue
})

// Update filter with debounce for text inputs
let updateTimeout: NodeJS.Timeout | null = null

const updateFilter = (value: any) => {
  localValue.value = value

  // Debounce für Text-Filter
  if (filterType.value === 'text' || filterType.value === 'number') {
    if (updateTimeout) {
      clearTimeout(updateTimeout)
    }

    updateTimeout = setTimeout(() => {
      emit('update:modelValue', value)
    }, 300)
  } else {
    // Sofortige Updates für andere Filter-Typen
    emit('update:modelValue', value)
  }
}
</script>

<style scoped>
.column-filter {
  min-width: 120px;
}

:deep(.dp__theme_light) {
  --dp-background-color: #ffffff;
  --dp-text-color: #212121;
  --dp-border-color: #ddd;
  --dp-hover-color: #f3f3f3;
  --dp-hover-text-color: #212121;
  --dp-primary-color: #1976d2;
  --dp-primary-disabled-color: #6bacea;
  --dp-primary-text-color: #f8f5f5;
}

:deep(.dp__input_wrap) {
  border: 1px solid #ddd;
  border-radius: 4px;
  min-height: 40px;
  font-size: 14px;
}

:deep(.dp__input) {
  padding: 8px 12px;
  font-size: 14px;
}
</style>