/**
 * TypeScript Definitionen für Table Filter System
 */

export interface TableFilterState {
  page: number
  itemsPerPage: number
  sortBy: Array<{ key: string; order: 'asc' | 'desc' }>
  search: string
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
