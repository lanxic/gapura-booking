import { create } from 'zustand'
import { persist } from 'zustand/middleware'

type CustomerUser = {
  id: string
  name: string
  email: string
  role: 'customer'
}

type AuthState = {
  user: CustomerUser | null
  token: string | null
  setAuth: (user: CustomerUser, token: string) => void
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
