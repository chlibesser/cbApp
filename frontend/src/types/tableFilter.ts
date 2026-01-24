/**
 * TypeScript Definitionen für Table Filter System
 */

// Filter-Operatoren je nach Spaltentyp
export type TextFilterOperator = 'contains' | 'equals' | 'startsWith' | 'endsWith' | 'isEmpty' | 'isNotEmpty'
export type NumberFilterOperator = 'equals' | 'gt' | 'gte' | 'lt' | 'lte' | 'between' | 'isEmpty' | 'isNotEmpty'
export type DateFilterOperator = 'equals' | 'before' | 'after' | 'between' | 'isEmpty' | 'isNotEmpty'
export type BooleanFilterOperator = 'equals'
export type SelectFilterOperator = 'in' | 'notIn'

export type FilterOperator =
  | TextFilterOperator
  | NumberFilterOperator
  | DateFilterOperator
  | BooleanFilterOperator
  | SelectFilterOperator

// Einzelner Spalten-Filter
export interface ColumnFilter {
  columnKey: string
  operator: FilterOperator
  value: string | number | boolean | string[] | [number | string, number | string] | null
}

// Operator-Optionen für UI
export interface FilterOperatorOption {
  value: FilterOperator
  label: string
}

// Verfügbare Operatoren je nach Typ
export const TEXT_OPERATORS: FilterOperatorOption[] = [
  { value: 'contains', label: 'Enthält' },
  { value: 'equals', label: 'Ist gleich' },
  { value: 'startsWith', label: 'Beginnt mit' },
  { value: 'endsWith', label: 'Endet mit' },
  { value: 'isEmpty', label: 'Ist leer' },
  { value: 'isNotEmpty', label: 'Ist nicht leer' }
]

export const NUMBER_OPERATORS: FilterOperatorOption[] = [
  { value: 'equals', label: 'Ist gleich' },
  { value: 'gt', label: 'Größer als' },
  { value: 'gte', label: 'Größer oder gleich' },
  { value: 'lt', label: 'Kleiner als' },
  { value: 'lte', label: 'Kleiner oder gleich' },
  { value: 'between', label: 'Zwischen' },
  { value: 'isEmpty', label: 'Ist leer' },
  { value: 'isNotEmpty', label: 'Ist nicht leer' }
]

export const DATE_OPERATORS: FilterOperatorOption[] = [
  { value: 'equals', label: 'Am' },
  { value: 'before', label: 'Vor' },
  { value: 'after', label: 'Nach' },
  { value: 'between', label: 'Zwischen' },
  { value: 'isEmpty', label: 'Ist leer' },
  { value: 'isNotEmpty', label: 'Ist nicht leer' }
]

export const BOOLEAN_OPERATORS: FilterOperatorOption[] = [
  { value: 'equals', label: 'Ist' }
]

export const SELECT_OPERATORS: FilterOperatorOption[] = [
  { value: 'in', label: 'Ist einer von' },
  { value: 'notIn', label: 'Ist keiner von' }
]

export interface TableFilterState {
  page: number
  itemsPerPage: number
  sortBy: Array<{ key: string; order: 'asc' | 'desc' }>
  search: string
  columnFilters?: ColumnFilter[]
  columnOrder?: string[]
  columnWidths?: Record<string, number>
}

export interface TableFilter {
  id: string
  account_id: string
  table_key: string
  name: string
  color: string
  filter_state: TableFilterState
  show_as_button: boolean
  created_at: string
  updated_at: string
}

export interface CreateTableFilterPayload {
  table_key: string
  name: string
  color?: string
  filter_state: TableFilterState
  show_as_button?: boolean
}

export interface UpdateTableFilterPayload {
  name?: string
  color?: string
  filter_state?: TableFilterState
  show_as_button?: boolean
}

export const FILTER_COLOR_PRESETS = [
  '#1976D2', // Blau (Primary)
  '#388E3C', // Grün
  '#F57C00', // Orange
  '#D32F2F', // Rot
  '#7B1FA2', // Lila
  '#00796B', // Teal
  '#455A64', // Blaugrau
  '#5D4037', // Braun
] as const

export type FilterColorPreset = typeof FILTER_COLOR_PRESETS[number]
