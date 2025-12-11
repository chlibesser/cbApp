<template>
  <div class="table-cell">
    <!-- Boolean -->
    <v-chip 
      v-if="column.type === 'boolean'" 
      :color="value ? 'success' : 'error'" 
      size="small"
      variant="flat"
    >
      <v-icon :icon="value ? 'mdi-check' : 'mdi-close'" />
      {{ value ? 'Ja' : 'Nein' }}
    </v-chip>

    <!-- Email -->
    <a 
      v-else-if="column.type === 'email' && value" 
      :href="`mailto:${value}`" 
      class="email-link"
    >
      {{ value }}
    </a>

    <!-- Phone -->
    <a 
      v-else-if="column.type === 'phone' && value" 
      :href="`tel:${value}`" 
      class="phone-link"
    >
      {{ value }}
    </a>

    <!-- URL -->
    <a 
      v-else-if="column.type === 'url' && value" 
      :href="value" 
      target="_blank" 
      rel="noopener noreferrer"
      class="url-link"
    >
      {{ formatUrl(value) }}
      <v-icon size="small" class="ml-1">mdi-open-in-new</v-icon>
    </a>

    <!-- Date -->
    <span 
      v-else-if="column.type === 'date' && value" 
      class="date-text"
    >
      {{ formatDate(value) }}
    </span>

    <!-- DateTime -->
    <span 
      v-else-if="column.type === 'datetime' && value" 
      class="datetime-text"
    >
      {{ formatDateTime(value) }}
    </span>

    <!-- Number -->
    <span 
      v-else-if="column.type === 'number' && value !== null && value !== undefined" 
      class="number-text"
    >
      {{ formatNumber(value) }}
    </span>

    <!-- Currency -->
    <span 
      v-else-if="column.type === 'currency' && value !== null && value !== undefined" 
      class="currency-text"
    >
      {{ formatCurrency(value) }}
    </span>

    <!-- Percentage -->
    <span 
      v-else-if="column.type === 'percentage' && value !== null && value !== undefined" 
      class="percentage-text"
    >
      {{ formatPercentage(value) }}
    </span>

    <!-- Select (with options) -->
    <v-chip
      v-else-if="column.type === 'select' && value"
      size="small"
      variant="outlined"
      :color="getSelectColor(value)"
    >
      {{ getSelectLabel(value) }}
    </v-chip>

    <!-- Text (default) -->
    <span 
      v-else
      class="text-content"
    >
      {{ formattedValue }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { TableColumn } from '../../../types/table'

interface Props {
  column: TableColumn
  value: any
}

const props = defineProps<Props>()

// Computed für formattierten Wert
const formattedValue = computed(() => {
  const { value, column } = props

  // Null/Undefined Handling
  if (value === null || value === undefined || value === '') {
    return '-'
  }

  // Custom Format-Funktion verwenden wenn vorhanden
  if (column.format) {
    return column.format(value)
  }

  // String zurückgeben
  return String(value)
})

// Formatting Funktionen
const formatDate = (value: string | Date) => {
  const date = new Date(value)
  if (isNaN(date.getTime())) return '-'
  
  return date.toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatDateTime = (value: string | Date) => {
  const date = new Date(value)
  if (isNaN(date.getTime())) return '-'
  
  return date.toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatNumber = (value: number | string) => {
  const num = Number(value)
  if (isNaN(num)) return String(value)
  
  return num.toLocaleString('de-DE')
}

const formatCurrency = (value: number | string, currency = 'EUR') => {
  const num = Number(value)
  if (isNaN(num)) return String(value)
  
  return num.toLocaleString('de-DE', {
    style: 'currency',
    currency: currency
  })
}

const formatPercentage = (value: number | string) => {
  const num = Number(value)
  if (isNaN(num)) return String(value)
  
  return (num * 100).toFixed(2) + '%'
}

const formatUrl = (url: string) => {
  // Zeige nur Domain für bessere Lesbarkeit
  try {
    const urlObj = new URL(url)
    return urlObj.hostname
  } catch {
    return url
  }
}

const getSelectLabel = (value: any) => {
  if (!props.column.filterOptions) return String(value)
  
  const option = props.column.filterOptions.find(opt => opt.value === value)
  return option?.text || String(value)
}

const getSelectColor = (value: any) => {
  // Basis-Farben für verschiedene Werte
  const colorMap: Record<string, string> = {
    'active': 'success',
    'inactive': 'error',
    'pending': 'warning',
    'draft': 'info',
    'published': 'success',
    'archived': 'secondary'
  }
  
  const stringValue = String(value).toLowerCase()
  return colorMap[stringValue] || 'primary'
}
</script>

<style scoped>
.table-cell {
  display: flex;
  align-items: center;
  min-height: 32px;
  word-break: break-word;
}

.email-link,
.phone-link,
.url-link {
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
  transition: color 0.2s;
}

.email-link:hover,
.phone-link:hover,
.url-link:hover {
  text-decoration: underline;
}

.date-text,
.datetime-text,
.number-text,
.currency-text,
.percentage-text {
  font-variant-numeric: tabular-nums;
}

.text-content {
  line-height: 1.4;
}

.v-chip {
  font-size: 0.875rem;
}
</style>