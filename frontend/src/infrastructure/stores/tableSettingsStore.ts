/**
 * Table Settings Store
 * Zentrales State-Management für Tabelleneinstellungen
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { tableSettingsService } from '@/shared/services/tableSettingsService'
import type { TableSettings } from '@/types/tableSettings'

export const useTableSettingsStore = defineStore('tableSettings', () => {
  // State - Map von tableKey zu Settings
  const settings = ref<Map<string, TableSettings>>(new Map())
  const loading = ref(false)
  const saving = ref(false)
  const error = ref<string | null>(null)

  // Debounce Timer für Auto-Save
  const saveTimers = new Map<string, ReturnType<typeof setTimeout>>()

  // Getters
  const getSettings = computed(() => {
    return (tableKey: string): TableSettings | null => {
      return settings.value.get(tableKey) || null
    }
  })

  const getColumnOrder = computed(() => {
    return (tableKey: string): string[] | null => {
      return settings.value.get(tableKey)?.column_order || null
    }
  })

  const getColumnWidths = computed(() => {
    return (tableKey: string): Record<string, number> | null => {
      return settings.value.get(tableKey)?.column_widths || null
    }
  })

  const getHiddenColumns = computed(() => {
    return (tableKey: string): string[] | null => {
      return settings.value.get(tableKey)?.hidden_columns || null
    }
  })

  // Actions

  /**
   * Lädt die Settings für eine Tabelle
   */
  async function loadSettings(tableKey: string): Promise<TableSettings> {
    loading.value = true
    error.value = null

    try {
      const data = await tableSettingsService.getSettings(tableKey)
      settings.value.set(tableKey, data)
      return data
    } catch (e: any) {
      error.value = e.message || 'Fehler beim Laden der Einstellungen'
      // Return empty settings on error
      return { column_order: null, column_widths: null, hidden_columns: null }
    } finally {
      loading.value = false
    }
  }

  /**
   * Speichert die Spaltenreihenfolge (mit Debounce)
   */
  function saveColumnOrder(tableKey: string, columnOrder: string[]): void {
    // Update local state immediately
    const current = settings.value.get(tableKey) || { column_order: null, column_widths: null, hidden_columns: null }
    settings.value.set(tableKey, { ...current, column_order: columnOrder })

    // Debounced save to backend
    debouncedSave(tableKey)
  }

  /**
   * Speichert die Spaltenbreiten (mit Debounce)
   */
  function saveColumnWidths(tableKey: string, columnWidths: Record<string, number>): void {
    // Update local state immediately
    const current = settings.value.get(tableKey) || { column_order: null, column_widths: null, hidden_columns: null }
    settings.value.set(tableKey, { ...current, column_widths: columnWidths })

    // Debounced save to backend
    debouncedSave(tableKey)
  }

  /**
   * Speichert die versteckten Spalten (mit Debounce)
   */
  function saveHiddenColumns(tableKey: string, hiddenColumns: string[]): void {
    // Update local state immediately
    const current = settings.value.get(tableKey) || { column_order: null, column_widths: null, hidden_columns: null }
    settings.value.set(tableKey, { ...current, hidden_columns: hiddenColumns })

    // Debounced save to backend
    debouncedSave(tableKey)
  }

  /**
   * Speichert eine einzelne Spaltenbreite (mit Debounce)
   */
  function saveColumnWidth(tableKey: string, columnKey: string, width: number): void {
    const current = settings.value.get(tableKey) || { column_order: null, column_widths: null, hidden_columns: null }
    const currentWidths = current.column_widths || {}

    settings.value.set(tableKey, {
      ...current,
      column_widths: { ...currentWidths, [columnKey]: width }
    })

    // Debounced save to backend
    debouncedSave(tableKey)
  }

  /**
   * Debounced Save - wartet 500ms bevor gespeichert wird
   */
  function debouncedSave(tableKey: string): void {
    // Clear existing timer
    const existingTimer = saveTimers.get(tableKey)
    if (existingTimer) {
      clearTimeout(existingTimer)
    }

    // Set new timer
    const timer = setTimeout(async () => {
      await persistSettings(tableKey)
      saveTimers.delete(tableKey)
    }, 500)

    saveTimers.set(tableKey, timer)
  }

  /**
   * Persistiert die Settings sofort im Backend
   */
  async function persistSettings(tableKey: string): Promise<void> {
    const current = settings.value.get(tableKey)
    if (!current) return

    saving.value = true
    error.value = null

    try {
      await tableSettingsService.saveSettings({
        table_key: tableKey,
        column_order: current.column_order,
        column_widths: current.column_widths,
        hidden_columns: current.hidden_columns
      })
    } catch (e: any) {
      error.value = e.message || 'Fehler beim Speichern der Einstellungen'
      console.warn('Failed to save table settings:', e)
    } finally {
      saving.value = false
    }
  }

  /**
   * Setzt die Settings für eine Tabelle zurück
   */
  async function resetSettings(tableKey: string): Promise<void> {
    // Cancel any pending saves
    const existingTimer = saveTimers.get(tableKey)
    if (existingTimer) {
      clearTimeout(existingTimer)
      saveTimers.delete(tableKey)
    }

    saving.value = true
    error.value = null

    try {
      await tableSettingsService.resetSettings(tableKey)
      settings.value.set(tableKey, { column_order: null, column_widths: null, hidden_columns: null })
    } catch (e: any) {
      error.value = e.message || 'Fehler beim Zurücksetzen der Einstellungen'
      throw e
    } finally {
      saving.value = false
    }
  }

  return {
    // State
    settings,
    loading,
    saving,
    error,

    // Getters
    getSettings,
    getColumnOrder,
    getColumnWidths,
    getHiddenColumns,

    // Actions
    loadSettings,
    saveColumnOrder,
    saveColumnWidths,
    saveColumnWidth,
    saveHiddenColumns,
    resetSettings,
  }
})
