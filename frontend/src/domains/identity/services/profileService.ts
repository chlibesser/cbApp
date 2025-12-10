import type { Profile, UpdateProfileRequest } from '../types'
import { apiClient } from '../../../core/api'

export class ProfileService {
  async getCurrentProfile(): Promise<any> {
    const response = await apiClient.get('/profile/current')
    return response.data.profile
  }

  async getProfiles(): Promise<any[]> {
    const response = await apiClient.get('/profile/profiles')
    return response.data.profiles
  }

  async switchProfile(profileId: string): Promise<any> {
    const response = await apiClient.post('/profile/switch', {
      profile_id: profileId
    })
    return response.data.profile
  }

  async getProfile(): Promise<Profile> {
    const response = await apiClient.get<Profile>('/profile')
    return response.data
  }

  async updateProfile(profileId: string, data: any): Promise<any> {
    const response = await apiClient.put(`/profile/${profileId}`, data)
    return response.data.profile
  }

  formatDisplayName(profile: any): string {
    if (profile.display_name) {
      return profile.display_name
    }
    
    if (profile.last_name) {
      return `${profile.first_name} ${profile.last_name}`
    }
    
    return profile.first_name
  }

  getPreference(profile: any, key: string, defaultValue?: any): any {
    if (!profile?.preferences) {
      return defaultValue
    }
    
    return profile.preferences[key] ?? defaultValue
  }
}

export const profileService = new ProfileService()
