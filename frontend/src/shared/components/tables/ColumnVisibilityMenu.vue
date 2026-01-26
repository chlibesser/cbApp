<template>
  <v-menu
    v-model="menuOpen"
    :target="menuTarget"
    location="bottom start"
    :close-on-content-click="false"
  >
    <v-card min-width="250" max-height="400" class="d-flex flex-column">
      <!-- Fixer Header -->
      <div class="d-flex align-center justify-space-between py-2 px-3 bg-grey-lighten-4 flex-shrink-0">
        <span class="text-subtitle-2">Spalten anzeigen</span>
        <v-btn
          icon="mdi-close"
          size="x-small"
          variant="text"
          density="compact"
          @click="menuOpen = false"
        />
      </div>
      <v-divider />

      <!-- Scrollbarer Inhalt -->
      <v-list density="compact" class="py-1 overflow-y-auto flex-grow-1">
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
            {{ translateTitle(column.title) }}
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

      <!-- Fixer Footer -->
      <v-divider />
      <v-card-actions class="py-1 px-2 flex-shrink-0">
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
import { useTranslations } from '@/core/localization/composables/useTranslations'
import type { TableColumn } from '@/types/table'

const { t } = useTranslations()

// Helper to translate title if it's a translation key
const translateTitle = (title: string): string => {
  if (title && title.includes('.') && !title.includes(' ')) {
    const translated = t(title)
    return translated !== title ? translated : title
  }
  return title
}

interface Props {
  columns: TableColumn[]
  hiddenColumns: string[]
  columnOrder: string[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:hiddenColumns': [columns: string[]]
}>()

// Menu State
const menuOpen = ref(false)
const menuTarget = ref<[number, number]>([0, 0])

// Alle Spalten (ohne actions) - in der richtigen Reihenfolge
const allColumns = computed(() => {
  const columnsWithoutActions = props.columns.filter(col => col.key !== 'actions')

  // Wenn keine benutzerdefinierte Reihenfolge, Original-Reihenfolge verwenden
  if (!props.columnOrder || props.columnOrder.length === 0) {
    return columnsWithoutActions
  }

  // Spalten nach columnOrder sortieren
  const columnMap = new Map(columnsWithoutActions.map(col => [col.key, col]))
  const ordered: TableColumn[] = []

  // Zuerst die Spalten in der definierten Reihenfolge
  for (const key of props.columnOrder) {
    const col = columnMap.get(key)
    if (col) {
      ordered.push(col)
      columnMap.delete(key)
    }
  }

  // Dann die restlichen Spalten (falls neue hinzugekommen sind)
  for (const col of columnMap.values()) {
    ordered.push(col)
  }

  return ordered
})

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
