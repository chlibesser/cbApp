import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import { authService } from '../auth'

export const authGuard = (
  to: RouteLocationNormalized,
  from: RouteLocationNormalized,
  next: NavigationGuardNext
) => {
  if (to.meta?.requiresAuth && !authService.isAuthenticated()) {
    next({ name: 'login' })
    return
  }

  if (to.meta?.guestOnly && authService.isAuthenticated()) {
    next({ name: 'dashboard' })
    return
  }

  next()
}

export const adminGuard = (
  to: RouteLocationNormalized,
  from: RouteLocationNormalized,
  next: NavigationGuardNext
) => {
  // TODO: Check if user has admin role
  // This will be implemented when we have the auth store
  if (to.meta?.requiresAdmin) {
    // For now, just check if authenticated
    if (!authService.isAuthenticated()) {
      next({ name: 'login' })
      return
    }
  }

  next()
}