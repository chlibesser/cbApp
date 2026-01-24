/**
 * Table Settings Service
 * API-Kommunikation für Tabelleneinstellungen
 */

import { apiClient } from '@/core/api'
import type { TableSettings, UpdateTableSettingsPayload } from '@/types/tableSettings'

class TableSettingsService {
  private readonly basePath = '/table-settings'

  /**
   * Holt die Settings für eine Tabelle
   */
  async getSettings(tableKey: string): Promise<TableSettings> {
    const response = await apiClient.get<{ data: TableSettings }>(
      `${this.basePath}?table_key=${encodeURIComponent(tableKey)}`
    )
    return response.data.data
  }

  /**
   * Speichert die Settings für eine Tabelle
   */
  async saveSettings(payload: UpdateTableSettingsPayload): Promise<TableSettings> {
    const response = await apiClient.put<{ data: TableSettings; message: string }>(
      this.basePath,
      payload
    )
    return response.data.data
  }

  /**
   * Setzt die Settings zurück
   */
  async resetSettings(tableKey: string): Promise<void> {
    await apiClient.delete(`${this.basePath}?table_key=${encodeURIComponent(tableKey)}`)
  }
}

export const tableSettingsService = new TableSettingsService()
