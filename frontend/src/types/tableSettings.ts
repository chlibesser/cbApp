/**
 * Table Settings Types
 * Typen für Benutzer-spezifische Tabelleneinstellungen
 */

export interface TableSettings {
  column_order: string[] | null
  column_widths: Record<string, number> | null
}

export interface UpdateTableSettingsPayload {
  table_key: string
  column_order?: string[] | null
  column_widths?: Record<string, number> | null
}
