import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { AuthUser } from '@/types'

type AuthState = {
  user: AuthUser | null
  token: string | null
  setAuth: (user: AuthUser, token: string) => void
  clear: () => void
  isAuthenticated: () => boolean
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      setAuth: (user, token) => set({ user, token }),
      clear: () => set({ user: null, token: null }),
      isAuthenticated: () => !!get().token,
    }),
    { name: 'amartha-auth' }
  )
)
