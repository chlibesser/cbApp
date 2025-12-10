import { describe, it, expect, beforeEach, vi } from 'vitest'
import { profileService } from '@/domains/identity/services/profileService'
import { apiClient } from '@/core/api'

// Mock apiClient
vi.mock('@/core/api', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}))

describe('ProfileService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('getCurrentProfile', () => {
    it('fetches current profile successfully', async () => {
      const mockProfile = {
        id: 'profile-1',
        first_name: 'Max',
        last_name: 'Mustermann',
        display_name: 'Max M.',
        tenant: {
          id: 'tenant-1',
          name: 'Test Firma GmbH'
        }
      }

      vi.mocked(apiClient.get).mockResolvedValue({
        data: { profile: mockProfile }
      })

      const result = await profileService.getCurrentProfile()

      expect(apiClient.get).toHaveBeenCalledWith('/profile/current')
      expect(result).toEqual(mockProfile)
    })

    it('returns null when no current profile', async () => {
      vi.mocked(apiClient.get).mockResolvedValue({
        data: { profile: null }
      })

      const result = await profileService.getCurrentProfile()

      expect(result).toBeNull()
    })

    it('handles API errors gracefully', async () => {
      vi.mocked(apiClient.get).mockRejectedValue(new Error('API Error'))

      await expect(profileService.getCurrentProfile()).rejects.toThrow('API Error')
    })
  })

  describe('getProfiles', () => {
    it('fetches all profiles for current user', async () => {
      const mockProfiles = [
        {
          id: 'profile-1',
          first_name: 'Max',
          tenant: { id: 'tenant-1', name: 'Firma 1' }
        },
        {
          id: 'profile-2', 
          first_name: 'Max',
          tenant: { id: 'tenant-2', name: 'Firma 2' }
        }
      ]

      vi.mocked(apiClient.get).mockResolvedValue({
        data: { profiles: mockProfiles }
      })

      const result = await profileService.getProfiles()

      expect(apiClient.get).toHaveBeenCalledWith('/profile/profiles')
      expect(result).toEqual(mockProfiles)
      expect(result).toHaveLength(2)
    })
  })

  describe('switchProfile', () => {
    it('switches to specified profile successfully', async () => {
      const profileId = 'profile-2'
      const mockProfile = {
        id: profileId,
        first_name: 'Max',
        tenant: { id: 'tenant-2', name: 'Andere Firma' }
      }

      vi.mocked(apiClient.post).mockResolvedValue({
        data: { 
          message: 'Profil erfolgreich gewechselt',
          profile: mockProfile 
        }
      })

      const result = await profileService.switchProfile(profileId)

      expect(apiClient.post).toHaveBeenCalledWith('/profile/switch', {
        profile_id: profileId
      })
      expect(result).toEqual(mockProfile)
    })

    it('rejects switch to unauthorized profile', async () => {
      const profileId = 'invalid-profile'

      vi.mocked(apiClient.post).mockRejectedValue({
        response: {
          status: 403,
          data: { message: 'Zugriff auf dieses Profil nicht erlaubt' }
        }
      })

      await expect(profileService.switchProfile(profileId)).rejects.toMatchObject({
        response: {
          status: 403,
          data: { message: 'Zugriff auf dieses Profil nicht erlaubt' }
        }
      })
    })
  })

  describe('updateProfile', () => {
    it('updates profile data successfully', async () => {
      const profileId = 'profile-1'
      const updateData = {
        first_name: 'Maximilian',
        display_name: 'Max M.',
        preferences: {
          theme: 'dark',
          language: 'en'
        }
      }

      const updatedProfile = {
        id: profileId,
        ...updateData,
        last_name: 'Mustermann'
      }

      vi.mocked(apiClient.put).mockResolvedValue({
        data: {
          message: 'Profil erfolgreich aktualisiert',
          profile: updatedProfile
        }
      })

      const result = await profileService.updateProfile(profileId, updateData)

      expect(apiClient.put).toHaveBeenCalledWith(`/profile/${profileId}`, updateData)
      expect(result).toEqual(updatedProfile)
    })

    it('validates profile data before update', async () => {
      const profileId = 'profile-1'
      const invalidData = {
        first_name: '', // Empty
        last_name: 'M' // Too short
      }

      vi.mocked(apiClient.put).mockRejectedValue({
        response: {
          status: 422,
          data: {
            message: 'Validation failed',
            errors: {
              first_name: ['First name is required'],
              last_name: ['Last name must be at least 2 characters']
            }
          }
        }
      })

      await expect(profileService.updateProfile(profileId, invalidData)).rejects.toMatchObject({
        response: {
          status: 422,
          data: {
            errors: {
              first_name: ['First name is required'],
              last_name: ['Last name must be at least 2 characters']
            }
          }
        }
      })
    })
  })

  describe('formatDisplayName', () => {
    it('returns display name when set', () => {
      const profile = {
        first_name: 'Maximilian',
        last_name: 'Mustermann',
        display_name: 'Max M.'
      }

      const result = profileService.formatDisplayName(profile)
      expect(result).toBe('Max M.')
    })

    it('falls back to full name when display name not set', () => {
      const profile = {
        first_name: 'Maximilian',
        last_name: 'Mustermann',
        display_name: null
      }

      const result = profileService.formatDisplayName(profile)
      expect(result).toBe('Maximilian Mustermann')
    })

    it('handles missing last name', () => {
      const profile = {
        first_name: 'Max',
        last_name: null,
        display_name: null
      }

      const result = profileService.formatDisplayName(profile)
      expect(result).toBe('Max')
    })
  })

  describe('getPreference', () => {
    it('returns specific preference value', () => {
      const profile = {
        preferences: {
          theme: 'dark',
          language: 'en',
          notifications: true
        }
      }

      expect(profileService.getPreference(profile, 'theme')).toBe('dark')
      expect(profileService.getPreference(profile, 'language')).toBe('en')
      expect(profileService.getPreference(profile, 'notifications')).toBe(true)
    })

    it('returns default value for missing preference', () => {
      const profile = { preferences: {} }

      expect(profileService.getPreference(profile, 'theme', 'light')).toBe('light')
      expect(profileService.getPreference(profile, 'missing')).toBeUndefined()
    })

    it('handles null preferences', () => {
      const profile = { preferences: null }

      expect(profileService.getPreference(profile, 'theme', 'light')).toBe('light')
    })
  })
})