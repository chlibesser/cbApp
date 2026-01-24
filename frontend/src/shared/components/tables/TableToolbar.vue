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
    <template v-if="enableFilters && filterContext && buttonFilters.length > 0">
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
        <template v-if="enableFilters && filterContext">
          <!-- Filter erstellen / aktualisieren -->
          <v-list-item @click="handleOpenSaveDialog">
            <template #prepend>
              <v-icon>mdi-plus</v-icon>
            </template>
            <v-list-item-title>Filter erstellen</v-list-item-title>
          </v-list-item>

          <v-list-item v-if="activeFilterId" @click="handleUpdateFilter">
            <template #prepend>
              <v-icon>mdi-content-save</v-icon>
            </template>
            <v-list-item-title>Filter aktualisieren</v-list-item-title>
          </v-list-item>

          <v-divider />

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
                  @click.stop="handleEditFilter(filter)"
                >
                  <v-icon size="small">mdi-pencil</v-icon>
                </v-btn>
                <v-btn
                  icon
                  variant="text"
                  size="x-small"
                  color="error"
                  @click.stop="handleDeleteFilter(filter)"
                >
                  <v-icon size="small">mdi-delete</v-icon>
                </v-btn>
              </template>
            </v-list-item>
          </v-list>

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
import { ref, computed, watch } from 'vue'
import { useTranslations } from '@/core/localization/composables/useTranslations'
import { useFilterContextOptional } from '@/shared/composables'
import type { TableFilter } from '@/types/tableFilter'

const { t } = useTranslations('admin.common')

// Filter Context via Inject (optional - funktioniert auch ohne)
const filterContext = useFilterContextOptional()

interface Props {
  tableKey?: string
  enableSearch?: boolean
  enableFilters?: boolean
  enableCreate?: boolean
  createButtonText?: string
  hasCustomSettings?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  tableKey: '',
  enableSearch: true,
  enableFilters: true,
  enableCreate: false,
  createButtonText: '',
  hasCustomSettings: false
})

// Nur noch @create Event wird emittiert (view-spezifisch)
const emit = defineEmits<{
  'create': []
}>()

// Computed: Filter-Daten aus Context oder leere Arrays
const filters = computed(() => filterContext?.filters.value ?? [])
const buttonFilters = computed(() => filterContext?.buttonFilters.value ?? [])
const activeFilterId = computed(() => filterContext?.activeFilterId.value ?? null)

// Search aus Context
const searchInput = ref(filterContext?.search.value ?? '')

// Sync search mit Context
watch(() => filterContext?.search.value, (val) => {
  if (val !== undefined) {
    searchInput.value = val
  }
})

// Local state
const settingsMenuOpen = ref(false)
let searchTimeout: ReturnType<typeof setTimeout> | null = null

// Debounced Search Handler
const handleSearchInput = (value: string | null) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  searchTimeout = setTimeout(() => {
    if (filterContext) {
      filterContext.search.value = value || ''
    }
  }, 300)
}

// Filter Handlers - Direkte Context-Aufrufe statt emit
const handleQuickFilterClick = (filter: TableFilter) => {
  if (!filterContext) return

  if (activeFilterId.value === filter.id) {
    filterContext.handleFilterReset()
  } else {
    filterContext.handleFilterApply(filter)
  }
}

const handleFilterClick = (filter: TableFilter) => {
  if (!filterContext) return

  if (activeFilterId.value === filter.id) {
    filterContext.handleFilterReset()
  } else {
    filterContext.handleFilterApply(filter)
  }
  settingsMenuOpen.value = false
}

const handleOpenSaveDialog = () => {
  settingsMenuOpen.value = false
  filterContext?.openSaveDialog(null)
}

const handleUpdateFilter = () => {
  settingsMenuOpen.value = false
  filterContext?.handleUpdateFilter()
}

const handleEditFilter = (filter: TableFilter) => {
  filterContext?.openSaveDialog(filter)
}

const handleDeleteFilter = (filter: TableFilter) => {
  filterContext?.handleDeleteFilter(filter)
}

const handleResetAllSettings = () => {
  filterContext?.handleResetAllSettings()
  settingsMenuOpen.value = false
}
</script>
