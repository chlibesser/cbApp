/**
 * Table Filter Service
 * API-Kommunikation für Table Filter System
 */

import { apiClient } from '@/core/api'
import type {
  TableFilter,
  CreateTableFilterPayload,
  UpdateTableFilterPayload
} from '@/types/tableFilter'

class TableFilterService {
  private readonly basePath = '/table-filters'

  async getFilters(tableKey: string): Promise<TableFilter[]> {
    const response = await apiClient.get<{ data: TableFilter[] }>(
      `${this.basePath}?table_key=${encodeURIComponent(tableKey)}`
    )
    return response.data.data
  }

  async createFilter(payload: CreateTableFilterPayload): Promise<TableFilter> {
    const response = await apiClient.post<{ data: TableFilter; message: string }>(
      this.basePath,
      payload
    )
    return response.data.data
  }

  async updateFilter(
    filterId: string,
    payload: UpdateTableFilterPayload
  ): Promise<TableFilter> {
    const response = await apiClient.patch<{ data: TableFilter; message: string }>(
      `${this.basePath}/${filterId}`,
      payload
    )
    return response.data.data
  }

  async deleteFilter(filterId: string): Promise<void> {
    await apiClient.delete(`${this.basePath}/${filterId}`)
  }
}

export const tableFilterService = new TableFilterService()
