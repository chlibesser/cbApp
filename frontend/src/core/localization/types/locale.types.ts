// Supported locales
export type SupportedLocale = 'de' | 'en'

// Locale information
export interface LocaleInfo {
  code: SupportedLocale
  name: string // Display name (e.g., "German")
  native: string // Native name (e.g., "Deutsch")
  flag: string // Flag code (e.g., "de")
}

// Translation namespace structure
// Supports nested keys like: admin.tenants.table.name
export interface TranslationMessages {
  [key: string]: string | TranslationMessages
}

// Namespace cache structure
export interface NamespaceCache {
  [namespace: string]: TranslationMessages
}

// Locale cache structure
export interface LocaleCache {
  [locale: string]: NamespaceCache
}

// API response for locales list
export interface LocalesResponse {
  success: boolean
  locales: LocaleInfo[]
  default_locale: SupportedLocale
}

// API response for translations
export interface TranslationsResponse {
  success: boolean
  locale: SupportedLocale
  namespace?: string
  translations: TranslationMessages
  count?: number
  empty?: boolean
}

// User locale preference response
export interface UserLocaleResponse {
  success: boolean
  preferred_locale: SupportedLocale
  current_locale: SupportedLocale
  display_name: string
  native_name: string
}

// Update locale request
export interface UpdateLocaleRequest {
  locale: SupportedLocale
}
