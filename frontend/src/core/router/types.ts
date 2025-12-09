export interface RouteGuard {
  requiresAuth?: boolean
  requiresAdmin?: boolean
  guestOnly?: boolean
}

export interface AppRoute {
  path: string
  name?: string
  component?: any
  meta?: RouteGuard & Record<string, any>
  children?: AppRoute[]
}