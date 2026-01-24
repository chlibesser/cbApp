<template>
  <div class="d-flex align-center gap-4">
    <!-- Search Field -->
    <v-text-field
      v-if="enableSearch"
      v-model="searchInput"
      prepend-inner-icon="mdi-magnify"
      :label="t('buttons.search')"
      variant="outlined"
      density="compact"
      hide-details
      clearable
      color="primary"
      style="max-width: 300px;"
      @update:model-value="handleSearchInput"
    />

    <!-- Filter Quick-Access Buttons -->
    <template v-if="enableFilters && tableKey && buttonFilters.length > 0">
      <v-btn
        v-for="filter in buttonFilters"
        :key="filter.id"
        :color="filter.color"
        :variant="activeFilterId === filter.id ? 'flat' : 'outlined'"
        size="small"
        class="ml-2"
        @click="handleQuickFilterClick(filter)"
      >
        {{ filter.name }}
      </v-btn>
    </template>

    <!-- Filter Settings Menu -->
    <v-menu
      v-model="settingsMenuOpen"
      :close-on-content-click="false"
      location="bottom end"
    >
      <template #activator="{ props: menuProps }">
        <v-btn
          v-bind="menuProps"
          variant="outlined"
          size="small"
          color="primary"
          class="ml-2"
        >
          <v-icon>mdi-filter-cog</v-icon>
        </v-btn>
      </template>

      <v-card min-width="280">
        <!-- Filter Section -->
        <template v-if="enableFilters && tableKey">
          <!-- Filter erstellen / aktualisieren Buttons -->
          <div class="pa-3 d-flex flex-column">
            <v-btn
              block
              color="primary"
              variant="outlined"
              prepend-icon="mdi-plus"
              @click="handleOpenSaveDialog"
            >
              Filter erstellen
            </v-btn>
            <v-btn
              v-if="activeFilterId"
              block
              color="primary"
              variant="outlined"
              prepend-icon="mdi-content-save"
              class="mt-4"
              @click="handleUpdateFilter"
            >
              Filter aktualisieren
            </v-btn>
          </div>

          <v-divider v-if="filters.length > 0" />

          <!-- Filter Liste -->
          <v-list v-if="filters.length > 0" density="compact" class="py-0">
            <v-list-item
              v-for="filter in filters"
              :key="filter.id"
              :active="activeFilterId === filter.id"
              @click="handleFilterClick(filter)"
            >
              <template #prepend>
                <v-avatar :color="filter.color" size="24">
                  <v-icon size="small" color="white">mdi-filter</v-icon>
                </v-avatar>
              </template>

              <v-list-item-title>{{ filter.name }}</v-list-item-title>

              <template #append>
                <v-btn
                  icon
                  variant="text"
                  size="x-small"
                  @click.stop="emit('filter-edit', filter)"
                >
                  <v-icon size="small">mdi-pencil</v-icon>
                </v-btn>
                <v-btn
                  icon
                  variant="text"
                  size="x-small"
                  color="error"
                  @click.stop="emit('filter-delete', filter)"
                >
                  <v-icon size="small">mdi-delete</v-icon>
                </v-btn>
              </template>
            </v-list-item>
          </v-list>

          <template v-if="activeFilterId">
            <v-list-item @click="handleResetFilters">
              <template #prepend>
                <v-icon>mdi-filter-off</v-icon>
              </template>
              <v-list-item-title>Filter zurücksetzen</v-list-item-title>
            </v-list-item>
          </template>

          <v-divider />
        </template>

        <!-- Alles zurücksetzen -->
        <v-list-item
          :disabled="!hasCustomSettings"
          color="error"
          @click="handleResetAllSettings"
        >
          <template #prepend>
            <v-icon>mdi-refresh</v-icon>
          </template>
          <v-list-item-title>Alles zurücksetzen</v-list-item-title>
        </v-list-item>
      </v-card>
    </v-menu>

    <!-- Create Button -->
    <v-btn
      v-if="enableCreate"
      color="primary"
      prepend-icon="mdi-plus"
      @click="emit('create')"
    >
      {{ createButtonText || t('buttons.create') }}
    </v-btn>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useTranslations } from '@/core/localization/composables/useTranslations'
import type { TableFilter } from '@/types/tableFilter'

const { t } = useTranslations('admin.common')

interface Props {
  tableKey?: string
  modelValue?: string
  enableSearch?: boolean
  enableFilters?: boolean
  enableCreate?: boolean
  createButtonText?: string
  activeFilterId?: string | null
  filters?: TableFilter[]
  buttonFilters?: TableFilter[]
  hasCustomSettings?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  tableKey: '',
  modelValue: '',
  enableSearch: true,
  enableFilters: true,
  enableCreate: false,
  createButtonText: '',
  activeFilterId: null,
  filters: () => [],
  buttonFilters: () => [],
  hasCustomSettings: false
})

const emit = defineEmits<{
  'update:modelValue': [search: string]
  'filter-apply': [filter: TableFilter]
  'filter-reset': []
  'filter-save': []
  'filter-update': []
  'filter-edit': [filter: TableFilter]
  'filter-delete': [filter: TableFilter]
  'create': []
  'reset-all-settings': []
}>()

// Local state
const searchInput = ref(props.modelValue)
const settingsMenuOpen = ref(false)
let searchTimeout: ReturnType<typeof setTimeout> | null = null

// Sync modelValue to local state
watch(() => props.modelValue, (val) => {
  searchInput.value = val
})

// Debounced Search Handler
const handleSearchInput = (value: string | null) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  searchTimeout = setTimeout(() => {
    emit('update:modelValue', value || '')
  }, 300)
}

// Filter Handlers
const handleQuickFilterClick = (filter: TableFilter) => {
  if (props.activeFilterId === filter.id) {
    emit('filter-reset')
  } else {
    emit('filter-apply', filter)
  }
}

const handleFilterClick = (filter: TableFilter) => {
  if (props.activeFilterId === filter.id) {
    emit('filter-reset')
  } else {
    emit('filter-apply', filter)
  }
  settingsMenuOpen.value = false
}

const handleResetFilters = () => {
  emit('filter-reset')
  settingsMenuOpen.value = false
}

const handleOpenSaveDialog = () => {
  settingsMenuOpen.value = false
  emit('filter-save')
}

const handleUpdateFilter = () => {
  settingsMenuOpen.value = false
  emit('filter-update')
}

const handleResetAllSettings = () => {
  emit('reset-all-settings')
  settingsMenuOpen.value = false
}
</script>
