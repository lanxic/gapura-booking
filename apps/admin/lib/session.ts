import { useAdminAuthStore } from '@/store/auth'

/** Wipe all client-side session state and redirect to login. */
export function clearSession(redirectTo = '/login') {
  // 1. Clear Zustand store (localStorage)
  useAdminAuthStore.getState().clear()

  // 2. Expire the cookies the middleware reads
  document.cookie = 'admin_token=; path=/; max-age=0; samesite=lax'
  document.cookie = 'admin_role=;  path=/; max-age=0; samesite=lax'

  // 3. Hard-navigate so middleware re-evaluates from a clean state
  window.location.href = redirectTo
}
