import type { Profile, UpdateProfileRequest } from '../types'
import { apiClient } from '../../../core/api'

export class ProfileService {
  async getProfile(): Promise<Profile> {
    const response = await apiClient.get<Profile>('/profile')
    return response.data
  }

  async updateProfile(data: UpdateProfileRequest): Promise<Profile> {
    const response = await apiClient.put<Profile>('/profile', data)
    return response.data
  }
}

export const profileService = new ProfileService()