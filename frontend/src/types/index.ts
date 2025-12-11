// Global type definitions for the application
export type * from '../core/auth/types'
export type * from '../core/api/types'
export type * from '../domains/identity/types'
export type * from '../domains/admin/types'

// Shared component types
export type * from './entity'
export type * from './table'

// Common utility types
export type Maybe<T> = T | null | undefined
export type Optional<T, K extends keyof T> = Pick<Partial<T>, K> & Omit<T, K>

// Form validation types
export type ValidationRule = (value: any) => boolean | string
export type FormValidation = {
  [key: string]: ValidationRule[]
}
