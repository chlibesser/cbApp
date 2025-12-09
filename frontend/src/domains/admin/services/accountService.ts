import type { Account, CreateAccountRequest } from '../types'
import type { PaginatedResponse } from '../../../core/api'
import { apiClient } from '../../../core/api'

export class AccountService {
  async getAccounts(page: number = 1): Promise<PaginatedResponse<Account>> {
    const response = await apiClient.get<PaginatedResponse<Account>>(`/admin/accounts?page=${page}`)
    return response.data
  }

  async getAccount(id: number): Promise<Account> {
    const response = await apiClient.get<Account>(`/admin/accounts/${id}`)
    return response.data
  }

  async createAccount(data: CreateAccountRequest): Promise<Account> {
    const response = await apiClient.post<Account>('/admin/accounts', data)
    return response.data
  }

  async deleteAccount(id: number): Promise<void> {
    await apiClient.delete(`/admin/accounts/${id}`)
  }
}

export const accountService = new AccountService()