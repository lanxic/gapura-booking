import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export type AdminRole = 'super_admin' | 'admin' | 'supervisor' | 'kasir' | 'scanner'

export type Permission =
  | 'products.manage'
  | 'availability.manage'
  | 'orders.view'
  | 'orders.manage'
  | 'vouchers.manage'
  | 'reports.view'
  | 'reports.export'
  | 'users.manage'
  | 'corrections.submit'
  | 'corrections.review'
  | 'activity_logs.view'
  | 'activity_logs.export'
  | 'settings.manage'
  | 'scanner.scan'
  | 'kasir.collect'
  | 'supervisor.corrections'

export type AdminUser = {
  id: string
  name: string
  email: string
  role: AdminRole
  permissions: Permission[]
  avatarUrl?: string
}

type AdminAuthState = {
  user: AdminUser | null
  token: string | null
  setAuth: (user: AdminUser, token: string) => void
  clear: () => void
  isAuthenticated: () => boolean
  hasPermission: (permission: Permission) => boolean
  can: (permission: Permission) => boolean
}

export const useAdminAuthStore = create<AdminAuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      setAuth: (user, token) => set({ user, token }),
      clear: () => set({ user: null, token: null }),
      isAuthenticated: () => !!get().token,
      hasPermission: (permission) =>
        get().user?.permissions.includes(permission) ?? false,
      can: (permission) =>
        get().user?.permissions.includes(permission) ?? false,
    }),
    { name: 'amartha-admin-auth' }
  )
)
