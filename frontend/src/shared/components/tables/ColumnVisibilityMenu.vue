<template>
  <v-menu
    v-model="menuOpen"
    :target="menuTarget"
    location="bottom start"
    :close-on-content-click="false"
  >
    <v-card min-width="220" max-height="400" class="overflow-auto">
      <v-card-title class="text-subtitle-2 py-2 px-3 bg-grey-lighten-4">
        Spalten anzeigen
      </v-card-title>
      <v-divider />
      <v-list density="compact" class="py-1">
        <v-list-item
          v-for="column in allColumns"
          :key="column.key"
          class="py-0"
        >
          <template #prepend>
            <v-checkbox
              :model-value="isColumnVisible(column.key)"
              :disabled="column.required"
              hide-details
              density="compact"
              class="mr-2"
              @update:model-value="toggleColumn(column.key, $event)"
            />
          </template>
          <v-list-item-title class="text-body-2">
            {{ column.title }}
          </v-list-item-title>
          <template #append>
            <v-chip
              v-if="column.required"
              size="x-small"
              variant="tonal"
              color="grey"
            >
              Pflicht
            </v-chip>
          </template>
        </v-list-item>
      </v-list>
      <v-divider />
      <v-card-actions class="py-1 px-2">
        <v-btn
          variant="text"
          size="small"
          color="primary"
          @click="showAllColumns"
        >
          Alle anzeigen
        </v-btn>
        <v-spacer />
        <v-btn
          variant="text"
          size="small"
          @click="menuOpen = false"
        >
          Schliessen
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-menu>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { TableColumn } from '@/types/table'

interface Props {
  columns: TableColumn[]
  hiddenColumns: string[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:hiddenColumns': [columns: string[]]
}>()

// Menu State
const menuOpen = ref(false)
const menuTarget = ref<[number, number]>([0, 0])

// Alle Spalten (ohne actions)
const allColumns = computed(() =>
  props.columns.filter(col => col.key !== 'actions')
)

// Prüfe ob Spalte sichtbar ist
const isColumnVisible = (key: string): boolean => {
  return !props.hiddenColumns.includes(key)
}

// Toggle Spalten-Sichtbarkeit
const toggleColumn = (key: string, visible: boolean | null) => {
  const column = props.columns.find(c => c.key === key)
  if (column?.required) return

  let newHiddenColumns: string[]
  if (visible) {
    newHiddenColumns = props.hiddenColumns.filter(k => k !== key)
  } else {
    newHiddenColumns = [...props.hiddenColumns, key]
  }
  emit('update:hiddenColumns', newHiddenColumns)
}

// Alle Spalten anzeigen
const showAllColumns = () => {
  emit('update:hiddenColumns', [])
}

// Öffne Menu an Position
const open = (event: MouseEvent) => {
  menuTarget.value = [event.clientX, event.clientY]
  menuOpen.value = true
}

// Expose für Parent
defineExpose({
  open
})
</script>
