import type { Account, CreateAccountRequest, UpdateAccountRequest } from '../types'
import type { PaginatedResponse } from '../../../core/api'
import { apiClient } from '../../../core/api'

export class AccountService {
  async getAccounts(page: number = 1): Promise<PaginatedResponse<Account>> {
    const response = await apiClient.get<PaginatedResponse<Account>>(`/admin/accounts?page=${page}`)
    return response.data
  }

  async getAccount(id: string): Promise<Account> {
    const response = await apiClient.get<Account>(`/admin/accounts/${id}`)
    return response.data
  }

  async createAccount(data: CreateAccountRequest): Promise<{ account: Account, message: string }> {
    const response = await apiClient.post<{ account: Account, message: string }>('/admin/accounts', data)
    return response.data
  }

  async updateAccount(id: string, data: UpdateAccountRequest): Promise<{ account: Account, message: string }> {
    const response = await apiClient.put<{ account: Account, message: string }>(`/admin/accounts/${id}`, data)
    return response.data
  }

  async deleteAccount(id: string): Promise<{ message: string }> {
    const response = await apiClient.delete<{ message: string }>(`/admin/accounts/${id}`)
    return response.data
  }
}

export const accountService = new AccountService()
